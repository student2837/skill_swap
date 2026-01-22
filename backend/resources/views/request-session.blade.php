@extends('layouts.app')

@section('title', 'Send Message – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  <!-- Main -->
  <main class="main-content" style="margin-left: 0; padding: 2rem;">
    <div style="margin-bottom: 1.5rem;">
      <button class="back-button" onclick="window.history.back()">
        ← Back
      </button>
    </div>
    <header class="topbar glass" style="margin-bottom: 1.5rem;">
      <h2>Send Message</h2>
    </header>

    <section>
      <!-- Form -->
      <div class="dash-card glass" style="max-width: 800px; margin: 0 auto;">
        <div class="dash-card-header">
          <h3>Ask the teacher</h3>
        </div>

        <!-- Message form -->
        <form class="profile-form" id="requestSessionForm">
          <label>
            <span>Your message</span>
            <textarea
              id="sessionNotes"
              rows="4"
              placeholder="Ask the teacher a question or share what you'd like to learn..."
            ></textarea>
          </label>

          <button type="submit" class="btn-primary profile-save-btn" id="sendMessageBtn">
            Send message
          </button>
        </form>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    
    // Validate authentication on page load
    async function validateAuthentication() {
      const token = localStorage.getItem("token");
      if (!token) {
        window.location.href = "{{ route('register') }}";
        return false;
      }
      
      apiClient.setToken(token);
      
      // Verify token is valid by trying to get user
      try {
        await apiClient.getUser();
        return true;
      } catch (err) {
        // Token is invalid or expired
        console.error("Invalid or expired token:", err);
        localStorage.removeItem("token");
        apiClient.clearToken();
        window.location.href = "{{ route('register') }}";
        return false;
      }
    }

    // Get skill ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const skillId = urlParams.get('skillId');

    if (!skillId) {
      alert("Skill ID not found. Redirecting to browse page.");
      window.location.href = "{{ route('browse') }}";
    }

    let currentSkill = null;
    let currentUser = null;

    // Load skill details and user (minimal - just to get teacher ID)
    async function loadSkillDetails() {
      try {
        // Load user first
        currentUser = await apiClient.getUser();
        
        // Load skill details
        currentSkill = await apiClient.getSkill(skillId);
        
        if (!currentSkill) {
          alert("Skill not found");
          window.location.href = "{{ route('browse') }}";
          return;
        }

        // Check if user is the teacher of this skill
        const teacherId = currentSkill.user_id || currentSkill.user?.id;
        if (currentUser && currentUser.id === teacherId) {
          alert("You cannot send a message to yourself.");
          window.location.href = "{{ route('browse') }}";
          return;
        }

      } catch (err) {
        console.error("Error loading data:", err);
        alert("Failed to load data: " + (err.message || "Unknown error"));
        window.location.href = "{{ route('browse') }}";
      }
    }

    // Handle form submission - only send message, don't create request
    document.getElementById('requestSessionForm').addEventListener('submit', async (e) => {
      e.preventDefault();

      // Verify authentication before submitting
      if (!apiClient.isAuthenticated()) {
        alert("You must be logged in to send a message. Redirecting to registration...");
        window.location.href = "{{ route('register') }}";
        return;
      }

      // Double-check by validating token
      try {
        await apiClient.getUser();
      } catch (err) {
        // Token is invalid
        localStorage.removeItem("token");
        apiClient.clearToken();
        alert("Your session has expired. Please log in again.");
        window.location.href = "{{ route('register') }}";
        return;
      }

      if (!currentSkill) {
        alert("Skill details not loaded. Please wait...");
        return;
      }

      if (!currentUser) {
        alert("User information not loaded. Please wait...");
        return;
      }

      const messageContent = document.getElementById('sessionNotes').value.trim();
      
      if (!messageContent) {
        alert('Please enter a message');
        return;
      }

      const sendBtn = document.getElementById('sendMessageBtn');
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';

      try {
        // Get the teacher's ID
        const teacherId = currentSkill.user_id || currentSkill.user?.id;
        
        if (!teacherId) {
          throw new Error('Could not find teacher information');
        }

        // Find or create conversation
        let conversation;
        try {
          // Try to get existing conversation
          const conversations = await apiClient.getUserConversations();
          conversation = conversations.find(c => 
            c.other_user.id === teacherId
          );
          
          if (!conversation) {
            // Create new conversation (without request_id since we're just messaging)
            conversation = await apiClient.createConversation(teacherId, null);
          } else {
            // Use existing conversation ID
            conversation = { id: conversation.id };
          }
        } catch (convErr) {
          console.error('Error getting/creating conversation:', convErr);
          throw new Error('Failed to create conversation: ' + (convErr.message || convErr.error || 'Unknown error'));
        }
        
        // Send message
        await apiClient.sendMessage(conversation.id, messageContent);
        
        alert('Message sent successfully!');
        // Clear the form
        document.getElementById('sessionNotes').value = '';
        
        // Optionally redirect to messages page
        window.location.href = '{{ route('messages') }}';
      } catch (err) {
        console.error('Error sending message:', err);
        
        // Handle authentication errors
        if (err.message && (err.message.includes('Unauthenticated') || err.message.includes('401'))) {
          localStorage.removeItem("token");
          apiClient.clearToken();
          alert("Your session has expired. Please log in again.");
          window.location.href = "{{ route('register') }}";
          return;
        }
        
        alert('Error: ' + (err.message || err.error || 'Failed to send message'));
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send message';
      }
    });

    // Load data on page load
    window.addEventListener('DOMContentLoaded', async () => {
      // Validate authentication first
      const isAuthenticated = await validateAuthentication();
      if (!isAuthenticated) {
        return; // Redirect already happened
      }
      
      // Load skill details to get teacher info
      await loadSkillDetails();
    });
  </script>
@endpush
