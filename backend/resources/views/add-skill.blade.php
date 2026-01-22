@extends('layouts.app')

@section('title', 'Add Skill – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Add new teaching skill</h2>
      <div class="topbar-actions">
        <a href="{{ route('my-skills') }}" class="btn-secondary">
          Back to My Skills
        </a>
      </div>
    </header>

    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3>Skill details</h3>
      </div>

      <!-- Skill creation form -->
      <form class="profile-form" id="addSkillForm">
        <label>
          <span>Skill title</span>
          <input
            type="text"
            id="skillTitle"
            placeholder="e.g. Python Programming for Beginners"
            required
          />
        </label>

        <label>
          <span>Category</span>
          <select id="skillCategory" required>
            <option value="">Select a category</option>
            <option value="programming">Programming</option>
            <option value="design">Design</option>
            <option value="music">Music</option>
            <option value="languages">Languages</option>
            <option value="other">Other</option>
          </select>
        </label>

        <label>
          <span>Course price (credits)</span>
          <input type="number" id="skillPrice" min="1" step="1" required />
        </label>

        <label>
          <span>Short description</span>
          <input type="text" id="skillShortDesc" />
        </label>

        <label>
          <span>Full description</span>
          <textarea
            id="skillDescription"
            rows="5"
            placeholder="Explain what students will learn, prerequisites, and structure."
            required
          ></textarea>
        </label>

        <label class="form-label">
          What you'll learn
          <textarea
            id="skillLearn"
            rows="4"
            placeholder="One outcome per line"
          ></textarea>
        </label>

        <div class="profile-roles">
          <span class="profile-roles-title">Lesson type</span>

          <div class="role-toggles">
            <div class="role-option">
              <input type="checkbox" id="lesson-online" />
              <label for="lesson-online">Online</label>
            </div>

            <div class="role-option">
              <input type="checkbox" id="lesson-inperson" />
              <label for="lesson-inperson">In-person</label>
            </div>
          </div>
        </div>

        <button type="button" class="btn-primary profile-save-btn" id="saveSkillBtn">
          Save skill
        </button>
      </form>
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

    // Handle button click
    document.getElementById("saveSkillBtn").addEventListener("click", async () => {
      const title = document.getElementById("skillTitle").value.trim();
      const category = document.getElementById("skillCategory").value;
      const price = parseInt(document.getElementById("skillPrice").value);
      const shortDesc = document.getElementById("skillShortDesc").value.trim();
      const description = document.getElementById("skillDescription").value.trim();
      const whatYoullLearn = document.getElementById("skillLearn").value.trim();
      
      // Extract lesson type (string enum - required, must be 'online' or 'inperson')
      const lessonOnline = document.getElementById("lesson-online").checked;
      const lessonInPerson = document.getElementById("lesson-inperson").checked;
      let lessonType = null;
      
      // Validation: lesson_type is required and can only be one value
      if (lessonOnline && lessonInPerson) {
        alert("Please select only one lesson type: Online or In-person");
        return;
      } else if (lessonOnline) {
        lessonType = "online";
      } else if (lessonInPerson) {
        lessonType = "inperson";
      } else {
        alert("Please select a lesson type (Online or In-person)");
        return;
      }

      // Validation: title is required, max 50 characters
      if (!title) {
        alert("Title is required");
        return;
      }
      if (title.length > 50) {
        alert("Title must be 50 characters or less");
        return;
      }

      // Validation: category is required
      if (!category) {
        alert("Please select a category");
        return;
      }

      // Validation: price is required, integer, min 1
      if (!price || isNaN(price)) {
        alert("Price is required and must be a valid number");
        return;
      }
      if (price < 1) {
        alert("Price must be at least 1");
        return;
      }

      // Validation: shortDesc max 255 characters
      if (shortDesc && shortDesc.length > 255) {
        alert("Short description must be 255 characters or less");
        return;
      }

      try {
        const skillData = {
          title: title,
          category: category,
          description: description || null,
          price: price,
          lesson_type: lessonType
        };
        
        if (shortDesc) {
          skillData.shortDesc = shortDesc;
        }
        
        if (whatYoullLearn) {
          skillData.what_youll_learn = whatYoullLearn;
        }

        const skill = await apiClient.createSkill(skillData);

        alert("Skill created successfully!");
        window.location.href = "{{ route('my-skills') }}";
      } catch (err) {
        alert(err.message || "Failed to create skill");
      }
    });
  </script>
@endpush
