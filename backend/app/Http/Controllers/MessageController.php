<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\SkillRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MessageController extends Controller
{
    /**
     * Send a message in a conversation
     */
    public function sendMessage(Request $request, $conversationId)
    {
        try {
            $request->validate([
                'content' => 'required|string|min:1'
            ]);

            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $conversation = Conversation::find($conversationId);
            
            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Ensure user is part of this conversation
            if (!$conversation->hasUser($user->id)) {
                return response()->json(['error' => 'Unauthorized: You are not part of this conversation'], 403);
            }

            // Determine receiver
            $toUserId = $conversation->getOtherUser($user->id)->id;

            if (!$toUserId) {
                return response()->json(['error' => 'Could not determine message recipient'], 400);
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'request_id' => $conversation->request_id, // Keep for backward compatibility
                'from_user_id' => $user->id,
                'to_user_id' => $toUserId,
                'content' => $request->input('content'),
            ]);

            // Update conversation's updated_at timestamp
            $conversation->touch();

            return response()->json([
                'message' => 'Message sent',
                'data' => $message->load('fromUser:id,name')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage(), [
                'conversation_id' => $conversationId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getConversationMessages($conversationId)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Ensure user is part of this conversation
            if (!$conversation->hasUser($user->id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $messages = Message::where('conversation_id', $conversationId)
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
                'messages' => $messages
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Mark messages as read in a conversation
     */
    public function markAsRead($conversationId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Ensure user is part of this conversation
            if (!$conversation->hasUser($user->id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            Message::where('conversation_id', $conversationId)
                ->where('to_user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'message' => 'Messages marked as read'
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    // ========== BACKWARD COMPATIBILITY METHODS (for request-based messages) ==========

    /**
     * Send a message in a request (backward compatibility)
     * This will find or create a conversation and send the message there
     */
    public function sendMessageToRequest(Request $request, $requestId)
    {
        try {
            $request->validate([
                'content' => 'required|string|min:1'
            ]);

            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $skillRequest = SkillRequest::with('skill')->find($requestId);
            
            if (!$skillRequest) {
                return response()->json(['error' => 'Request not found'], 404);
            }

            // Ensure user is part of this request
            if (
                $skillRequest->student_id != $user->id &&
                $skillRequest->skill->user_id != $user->id
            ) {
                return response()->json(['error' => 'Unauthorized: You are not part of this request'], 403);
            }

            // Get or create conversation
            $conversation = Conversation::getOrCreate(
                $skillRequest->student_id,
                $skillRequest->skill->user_id,
                $skillRequest->id
            );

            // Determine receiver
            $toUserId = ($user->id == $skillRequest->student_id)
                ? $skillRequest->skill->user_id
                : $skillRequest->student_id;

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'request_id' => $skillRequest->id,
                'from_user_id' => $user->id,
                'to_user_id' => $toUserId,
                'content' => $request->input('content'),
            ]);

            // Update conversation's updated_at timestamp
            $conversation->touch();

            return response()->json([
                'message' => 'Message sent',
                'data' => $message->load('fromUser:id,name')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }
}
