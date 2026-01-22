<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function getUserConversations()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversations = Conversation::where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id)
                ->with(['userOne:id,name,email', 'userTwo:id,name,email', 'latestMessage.fromUser:id,name'])
                ->withCount(['messages as unread_count' => function ($query) use ($user) {
                    $query->where('to_user_id', $user->id)
                          ->whereNull('read_at');
                }])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($conversation) use ($user) {
                    $otherUser = $conversation->getOtherUser($user->id);
                    $latestMessage = $conversation->latestMessage;
                    
                    return [
                        'id' => $conversation->id,
                        'other_user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->name,
                            'email' => $otherUser->email,
                        ],
                        'latest_message' => $latestMessage ? [
                            'id' => $latestMessage->id,
                            'content' => $latestMessage->content,
                            'from_user_id' => $latestMessage->from_user_id,
                            'created_at' => $latestMessage->created_at,
                        ] : null,
                        'unread_count' => $conversation->unread_count ?? 0,
                        'updated_at' => $conversation->updated_at,
                    ];
                });

            return response()->json([
                'conversations' => $conversations
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching conversations: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get a specific conversation with messages
     */
    public function getConversation($conversationId)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversation = Conversation::with(['userOne:id,name,email', 'userTwo:id,name,email'])
                ->find($conversationId);

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Check if user is part of this conversation
            if (!$conversation->hasUser($user->id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $otherUser = $conversation->getOtherUser($user->id);
            
            $messages = $conversation->messages()
                ->with('fromUser:id,name')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) use ($user) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'from_user_id' => $message->from_user_id,
                        'to_user_id' => $message->to_user_id,
                        'is_me' => $message->from_user_id == $user->id,
                        'sender_name' => $message->fromUser->name ?? 'Unknown',
                        'created_at' => $message->created_at,
                        'read_at' => $message->read_at,
                    ];
                });

            return response()->json([
                'conversation' => [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'email' => $otherUser->email,
                    ],
                ],
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching conversation: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Create a conversation between two users
     */
    public function createConversation(Request $request)
    {
        try {
            $request->validate([
                'other_user_id' => 'required|exists:users,id',
                'request_id' => 'nullable|exists:requests,id'
            ]);

            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $otherUserId = $request->input('other_user_id');
            
            if ($user->id == $otherUserId) {
                return response()->json(['error' => 'Cannot create conversation with yourself'], 400);
            }

            $conversation = Conversation::getOrCreate(
                $user->id,
                $otherUserId,
                $request->input('request_id')
            );

            $otherUser = $conversation->getOtherUser($user->id);

            return response()->json([
                'message' => 'Conversation created',
                'conversation' => [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'email' => $otherUser->email,
                    ],
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating conversation: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }
}
