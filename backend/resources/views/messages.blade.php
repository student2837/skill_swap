@extends('layouts.app')

@section('title', 'Messages – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  <script>
    // Hide sidebar on messages page
    (function() {
      const userSidebar = document.getElementById('userSidebarComponent');
      const adminSidebar = document.getElementById('adminSidebarComponent');
      if (userSidebar) userSidebar.style.display = 'none';
      if (adminSidebar) adminSidebar.style.display = 'none';
      
      // Make main content full width
      const mainContent = document.querySelector('.main-content');
      if (mainContent) {
        mainContent.style.marginLeft = '0';
        mainContent.style.width = '100%';
      }
    })();
  </script>

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <a href="#" class="back-button" id="backButton" style="margin: 0;" onclick="event.preventDefault(); goBack();">
          ←
        </a>
        <h2 style="margin: 0;">Messages</h2>
      </div>
    </header>

    <section>
      <!-- Conversations List -->
      <div class="dash-card glass messages-list-card-full">
        <div class="dash-card-header">
          <h3>Conversations</h3>
          <button class="btn-small" onclick="loadConversations()" title="Refresh">Refresh</button>
        </div>
        
        <p style="padding: 0 1.5rem 1rem 1.5rem; color: var(--text-muted); font-size: 0.875rem; margin: 0;">Select a conversation to start chatting</p>

        <div class="message-list" id="conversationsList">
          <p style="padding: 1rem; text-align: center; color: var(--text-muted);">Loading conversations...</p>
        </div>
      </div>

      <!-- Right Panel: Chat Window (shown when conversation is selected) -->
      <div class="dash-card glass messages-thread-card" id="threadCard" style="display: none;">
        <!-- Chat Window -->
        <div id="chatWindow">
          <!-- Chat Header -->
          <div class="dash-card-header" style="border-bottom: 1px solid var(--border-soft); padding-bottom: 0.75rem;">
            <div>
              <h3 id="threadUserName" style="margin: 0; font-size: 1.1rem;">—</h3>
              <span class="thread-subtitle" id="threadSubtitle" style="font-size: 0.85rem; color: var(--text-muted);">
                Active now
              </span>
            </div>
          </div>

          <!-- Messages Area -->
          <div class="thread-body" id="threadBody">
            <p style="padding: 1rem; text-align: center; color: var(--text-muted);">Loading messages...</p>
          </div>

          <!-- Message Input -->
          <form class="thread-input-row" id="messageForm">
            <input
              type="text"
              id="messageInput"
              placeholder="Type your message…"
              disabled
              autocomplete="off"
            />
            <button type="submit" id="sendBtn" disabled>Send</button>
          </form>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const token = localStorage.getItem("token");
    if (!token) window.location.href = "{{ route('login') }}";

    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    if (token) {
      apiClient.setToken(token);
    }

    // Set back button destination based on user role
    async function goBack() {
      try {
        if (apiClient.isAuthenticated()) {
          const user = await apiClient.getUser();
          if (user.is_admin) {
            window.location.href = "{{ route('admin.dashboard') }}";
          } else {
            window.location.href = "{{ route('dashboard') }}";
          }
        } else {
          window.location.href = "{{ route('dashboard') }}";
        }
      } catch (err) {
        console.error("Error checking user for back button:", err);
        window.location.href = "{{ route('dashboard') }}";
      }
    }

    let currentUser = null;
    let currentConversationId = null;
    let refreshInterval = null;
    const urlParams = new URLSearchParams(window.location.search);
    const initialConversationId = parseInt(urlParams.get('conversation_id') || '', 10) || null;
    const initialUserName = urlParams.get('user') ? decodeURIComponent(urlParams.get('user')) : null;
    
    function setThreadHeader(userName, isAdmin = false) {
      const titleEl = document.getElementById('threadUserName');
      if (!titleEl) return;
      titleEl.textContent = userName || '—';
      
      // remove old badge if any
      const old = document.getElementById('threadAdminBadge');
      if (old) old.remove();
      
      if (isAdmin) {
        const badge = document.createElement('span');
        badge.id = 'threadAdminBadge';
        badge.className = 'tag tag-blue';
        badge.textContent = 'Admin';
        badge.style.cssText = 'margin-left: 0.6rem; font-size: 0.72rem; padding: 0.2rem 0.55rem; vertical-align: middle;';
        titleEl.insertAdjacentElement('afterend', badge);
      }
    }

    // Load current user
    async function loadCurrentUser() {
      try {
        currentUser = await apiClient.getUser();
      } catch (err) {
        console.error("Error loading user:", err);
        window.location.href = "{{ route('login') }}";
      }
    }

    // Format time for display
    function formatTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays === 1) return 'Yesterday';
      if (diffDays < 7) return `${diffDays}d ago`;
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    // Get initials for avatar
    function getInitials(name) {
      if (!name) return '?';
      const parts = name.trim().split(' ');
      if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    // Load conversations list
    async function loadConversations() {
      try {
        const conversations = await apiClient.getUserConversations();
        const listContainer = document.getElementById('conversationsList');
        
        if (!conversations || conversations.length === 0) {
          listContainer.innerHTML = '<p style="padding: 1rem; text-align: center; color: var(--text-muted);">No conversations yet. Start a session request to begin chatting!</p>';
          return;
        }

        listContainer.innerHTML = conversations.map(conv => {
          const initials = getInitials(conv.other_user.name);
          const latestMsg = conv.latest_message;
          const preview = latestMsg 
            ? (latestMsg.content.length > 40 ? latestMsg.content.substring(0, 40) + '...' : latestMsg.content)
            : 'No messages yet';
          const time = latestMsg ? formatTime(latestMsg.created_at) : '';
          const adminBadge = conv.other_user?.is_admin
            ? `<span class="tag tag-blue" style="font-size:0.7rem; padding:0.18rem 0.5rem;">Admin</span>`
            : '';
          const unreadBadge = conv.unread_count > 0 
            ? `<span class="unread-badge">${conv.unread_count}</span>` 
            : '';
          const isActive = currentConversationId === conv.id ? 'active-thread' : '';
          
          return `
            <div class="message-item ${isActive}" 
                 data-conversation-id="${conv.id}"
                 data-user-name="${conv.other_user.name.replace(/"/g, '&quot;')}"
                 data-user-is-admin="${conv.other_user?.is_admin ? '1' : '0'}">
              <div class="avatar">${initials}</div>
              <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                  <p class="msg-sender">${conv.other_user.name}</p>
                  ${adminBadge}
                  ${unreadBadge}
                </div>
                <p class="msg-text">${preview}</p>
              </div>
              <span class="msg-time">${time}</span>
            </div>
          `;
        }).join('');
        
        // Add click handlers to conversation items
        listContainer.querySelectorAll('.message-item').forEach(item => {
          item.addEventListener('click', function() {
            const conversationId = parseInt(this.getAttribute('data-conversation-id'));
            const userName = this.getAttribute('data-user-name');
            const isAdmin = this.getAttribute('data-user-is-admin') === '1';
            selectConversation(conversationId, userName, this, isAdmin);
          });
        });
      } catch (err) {
        console.error("Error loading conversations:", err);
        document.getElementById('conversationsList').innerHTML = '<p style="padding: 1rem; text-align: center; color: var(--text-danger);">Error loading conversations</p>';
      }
    }

    // Select a conversation
    async function selectConversation(conversationId, userName, clickedElement, isAdmin = false) {
      currentConversationId = conversationId;
      
      // Hide conversation list and show chat window
      const conversationListCard = document.querySelector('.messages-list-card-full');
      const threadCard = document.getElementById('threadCard');
      
      if (conversationListCard) {
        conversationListCard.style.display = 'none';
      }
      
      threadCard.style.display = 'flex';
      threadCard.style.flexDirection = 'column';
      
      // Show chat window
      document.getElementById('chatWindow').style.display = 'flex';
      document.getElementById('chatWindow').style.flexDirection = 'column';
      
      // Update UI
      setThreadHeader(userName, isAdmin);
      document.getElementById('threadSubtitle').textContent = 'Active now';
      
      // Enable message form
      const messageInput = document.getElementById('messageInput');
      const sendBtn = document.getElementById('sendBtn');
      messageInput.disabled = false;
      sendBtn.disabled = false;
      messageInput.focus();
      
      // Update active state in list (if list is still visible)
      document.querySelectorAll('.message-item').forEach(item => {
        item.classList.remove('active-thread');
      });
      if (clickedElement) {
        clickedElement.classList.add('active-thread');
      }
      
      // Load messages
      await loadMessages(conversationId);
      
      // Add back button
      addBackButton();
      
      // Mark as read
      try {
        await apiClient.markMessagesAsRead(conversationId);
        await loadConversations(); // Refresh to update unread count
      } catch (err) {
        console.error("Error marking as read:", err);
      }
      
      // Start auto-refresh
      startAutoRefresh();
    }
    
    // Add back button to chat window to return to conversation list
    function addBackButton() {
      const threadCard = document.getElementById('threadCard');
      const chatHeader = threadCard.querySelector('.dash-card-header');
      
      // Check if back button already exists
      if (chatHeader && !chatHeader.querySelector('.back-to-conversations')) {
        const backBtn = document.createElement('button');
        backBtn.className = 'back-to-conversations';
        backBtn.innerHTML = '← Back';
        backBtn.style.cssText = 'background: transparent; border: none; color: var(--text-main); cursor: pointer; padding: 0.5rem; margin-right: 1rem; font-size: 0.9rem;';
        backBtn.onclick = () => {
          document.getElementById('threadCard').style.display = 'none';
          document.querySelector('.messages-list-card-full').style.display = 'block';
          stopAutoRefresh();
          currentConversationId = null;
        };
        
        if (chatHeader.querySelector('div')) {
          chatHeader.insertBefore(backBtn, chatHeader.querySelector('div'));
        }
      }
    }

    // Load messages for a conversation
    async function loadMessages(conversationId) {
      try {
        const response = await apiClient.getConversation(conversationId);
        const messages = response.messages || [];
        const threadBody = document.getElementById('threadBody');
        
        if (!messages || messages.length === 0) {
          threadBody.innerHTML = '<p style="padding: 1rem; text-align: center; color: var(--text-muted);">No messages yet. Start the conversation!</p>';
          return;
        }

        threadBody.innerHTML = messages.map(msg => {
          const isMe = msg.is_me || msg.from_user_id === currentUser.id;
          const time = formatTime(msg.created_at);
          
          return `
            <div class="bubble-row ${isMe ? 'right' : ''}">
              <div class="bubble ${isMe ? 'bubble-me' : 'bubble-other'}">
                ${msg.content}
                <span style="display: block; font-size: 0.7rem; opacity: 0.7; margin-top: 0.25rem; text-align: ${isMe ? 'right' : 'left'};">
                  ${time}
                </span>
              </div>
            </div>
          `;
        }).join('');
        
        // Scroll to bottom
        threadBody.scrollTop = threadBody.scrollHeight;
      } catch (err) {
        console.error("Error loading messages:", err);
        document.getElementById('threadBody').innerHTML = '<p style="padding: 1rem; text-align: center; color: var(--text-danger);">Error loading messages</p>';
      }
    }

    // Send message
    document.getElementById('messageForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      if (!currentConversationId) {
        alert('Please select a conversation first');
        return;
      }
      
      const input = document.getElementById('messageInput');
      const content = input.value.trim();
      
      if (!content) {
        return;
      }
      
      const sendBtn = document.getElementById('sendBtn');
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';
      
      try {
        await apiClient.sendMessage(currentConversationId, content);
        input.value = '';
        
        // Reload messages and conversations
        await loadMessages(currentConversationId);
        await loadConversations();
        
        // Scroll to bottom
        const threadBody = document.getElementById('threadBody');
        threadBody.scrollTop = threadBody.scrollHeight;
      } catch (err) {
        console.error("Error sending message:", err);
        alert('Error: ' + (err.message || err.error || 'Failed to send message'));
      } finally {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send';
        input.focus();
      }
    });

    // Enable/disable send button based on input
    document.getElementById('messageInput').addEventListener('input', function() {
      const sendBtn = document.getElementById('sendBtn');
      sendBtn.disabled = !this.value.trim() || !currentConversationId;
    });

    // Auto-refresh messages every 3 seconds
    function startAutoRefresh() {
      if (refreshInterval) {
        clearInterval(refreshInterval);
      }
      
      refreshInterval = setInterval(async () => {
        if (currentConversationId) {
          await loadMessages(currentConversationId);
          await loadConversations();
        }
      }, 3000);
    }

    // Stop auto-refresh
    function stopAutoRefresh() {
      if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
      }
    }

    // Initialize
    window.addEventListener('DOMContentLoaded', async () => {
      await loadCurrentUser();
      await loadConversations();
      
      // If we were redirected here from admin "Message" button, open the conversation directly
      if (initialConversationId) {
        try {
          const response = await apiClient.getConversation(initialConversationId);
          const otherUser = response?.conversation?.other_user;
          const otherName = initialUserName || otherUser?.name || 'Conversation';
          const otherIsAdmin = !!otherUser?.is_admin;
          await selectConversation(initialConversationId, otherName, null, otherIsAdmin);
        } catch (err) {
          console.error('Error opening initial conversation:', err);
        }
      }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
      stopAutoRefresh();
    });
  </script>
@endpush
