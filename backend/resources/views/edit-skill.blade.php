@extends('layouts.app')

@section('title', 'Edit Skill – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Edit skill</h2>
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

      <!-- Edit skill form -->
      <form class="profile-form" id="editSkillForm">
        <label>
          <span>Skill title</span>
          <input type="text" id="skillTitle" required />
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
          <textarea id="skillDescription" rows="5"></textarea>
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
          <span class="profile-roles-title">
            Lesson type
          </span>

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

        <button
          type="submit"
          class="btn-primary profile-save-btn"
        >
          Save changes
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

    // Get skill ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const skillId = urlParams.get('id');

    if (!skillId) {
      alert("Skill ID not found");
      window.location.href = "{{ route('my-skills') }}";
    }

    // Load skill data
    async function loadSkill() {
      try {
        const skills = await apiClient.getTeachingSkills();
        const skill = skills.find(s => s.id == skillId);
        
        if (!skill) {
          alert("Skill not found");
          window.location.href = "{{ route('my-skills') }}";
          return;
        }

        // Populate form fields
        document.getElementById("skillTitle").value = skill.title || '';
        document.getElementById("skillPrice").value = skill.price || 0;
        document.getElementById("skillDescription").value = skill.description || '';
        document.getElementById("skillShortDesc").value = skill.shortDesc || '';
        document.getElementById("skillLearn").value = skill.what_youll_learn || skill.whatYoullLearn || '';

        // Set category (use the category field from skill, which is an enum)
        if (skill.category) {
          document.getElementById("skillCategory").value = skill.category.toLowerCase();
        }

        // Set lesson type checkboxes
        if (skill.lesson_type === 'online') {
          document.getElementById("lesson-online").checked = true;
        } else if (skill.lesson_type === 'inperson') {
          document.getElementById("lesson-inperson").checked = true;
        }
      } catch (err) {
        console.error("Error loading skill:", err);
        alert("Failed to load skill data");
      }
    }

    // Handle form submission
    document.getElementById("editSkillForm").addEventListener("submit", async (e) => {
      e.preventDefault();

      const title = document.getElementById("skillTitle").value.trim();
      const category = document.getElementById("skillCategory").value.toLowerCase().trim();
      const price = parseInt(document.getElementById("skillPrice").value);
      const shortDesc = document.getElementById("skillShortDesc").value.trim();
      const description = document.getElementById("skillDescription").value.trim();
      const whatYoullLearn = document.getElementById("skillLearn").value.trim();
      
      // Get lesson type
      const lessonOnline = document.getElementById("lesson-online").checked;
      const lessonInPerson = document.getElementById("lesson-inperson").checked;
      let lessonType = null;
      
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

      // Validation
      if (!title) {
        alert("Title is required");
        return;
      }
      if (title.length > 50) {
        alert("Title must be 50 characters or less");
        return;
      }
      if (!price || isNaN(price) || price < 1) {
        alert("Price must be a valid number and at least 1");
        return;
      }
      if (shortDesc && shortDesc.length > 255) {
        alert("Short description must be 255 characters or less");
        return;
      }

      // Validation: category is required and must be one of the valid values
      const validCategories = ['music', 'programming', 'design', 'languages', 'other'];
      if (!category) {
        alert("Category is required");
        return;
      }
      if (!validCategories.includes(category)) {
        alert(`Category must be one of: ${validCategories.join(', ')}`);
        return;
      }

      try {
        const skillData = {
          title: title,
          category: category,
          description: description,
          price: price,
          lesson_type: lessonType
        };
        
        if (shortDesc) {
          skillData.shortDesc = shortDesc;
        }
        
        if (whatYoullLearn) {
          skillData.what_youll_learn = whatYoullLearn;
        }

        await apiClient.updateSkill(skillId, skillData);

        alert("Skill updated successfully!");
        window.location.href = "{{ route('my-skills') }}";
      } catch (err) {
        alert(err.message || "Failed to update skill");
        console.error("Error updating skill:", err);
      }
    });

    loadSkill();
  </script>
@endpush
