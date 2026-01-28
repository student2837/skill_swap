@extends('layouts.app')

@section('title', 'Edit Skill – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

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
            <option value="">Loading categories...</option>
          </select>
        </label>

        <label class="filter-label">
          <span>Course price (credits)</span>
          <div class="credit-input-wrapper">
            <input type="text" id="skillPrice" class="credit-input" placeholder="0" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required />
            <span class="credit-input-suffix">credits</span>
          </div>
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

    // Load categories dynamically
    async function loadCategories() {
      try {
        const categories = await apiClient.listCategories();
        const categorySelect = document.getElementById('skillCategory');
        
        if (categories.length === 0) {
          categorySelect.innerHTML = '<option value="">No categories available</option>';
          return;
        }
        
        // Sort categories: alphabetically first, then "other" should always be last
        const sortedCategories = [...categories].sort((a, b) => {
          const aName = (a.name || '').toLowerCase();
          const bName = (b.name || '').toLowerCase();
          if (aName === 'other') return 1;
          if (bName === 'other') return -1;
          return aName.localeCompare(bName); // Alphabetical order for others
        });
        
        categorySelect.innerHTML = '<option value="">Select a category</option>' +
          sortedCategories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
      } catch (err) {
        console.error('Error loading categories:', err);
        document.getElementById('skillCategory').innerHTML = '<option value="">Error loading categories</option>';
      }
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

        // Set category (use categories relationship)
        if (skill.categories && skill.categories.length > 0) {
          document.getElementById("skillCategory").value = skill.categories[0].id;
        } else if (skill.category) {
          // Fallback: try to find category by name for backward compatibility
          const categories = await apiClient.listCategories();
          const matchingCat = categories.find(c => c.name.toLowerCase() === skill.category.toLowerCase());
          if (matchingCat) {
            document.getElementById("skillCategory").value = matchingCat.id;
          }
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
      const categoryId = parseInt(document.getElementById("skillCategory").value);
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
          category_id: categoryId,
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

    // Integer-only validation function for credit inputs
    function validateIntegerInput(value) {
      const cleaned = value.replace(/[^0-9]/g, '');
      const normalized = cleaned === '' ? '' : String(parseInt(cleaned, 10) || '');
      return normalized;
    }
    
    // Setup professional credit input for skill price
    function setupCreditInput(inputId) {
      const input = document.getElementById(inputId);
      if (!input) return;
      
      // Integer-only input handling
      input.addEventListener('input', (e) => {
        const originalValue = e.target.value;
        const validatedValue = validateIntegerInput(originalValue);
        
        if (originalValue !== validatedValue) {
          e.target.value = validatedValue;
        }
        
        // Add validation classes
        const numValue = parseInt(validatedValue, 10);
        input.classList.remove('valid-input', 'invalid-input');
        if (validatedValue && numValue >= 1) {
          input.classList.add('valid-input');
        } else if (validatedValue && numValue === 0) {
          input.classList.add('invalid-input');
        }
      });
      
      // Focus/blur animations
      input.addEventListener('focus', () => {
        input.closest('label')?.classList.add('credit-input-focused');
      });
      
      input.addEventListener('blur', () => {
        input.closest('label')?.classList.remove('credit-input-focused');
        const value = parseInt(input.value, 10);
        if (input.value && (!value || value < 1)) {
          input.value = '';
          input.classList.remove('valid-input', 'invalid-input');
        } else if (value >= 1) {
          input.classList.remove('invalid-input');
          input.classList.add('valid-input');
        }
      });
      
      // Prevent invalid characters
      input.addEventListener('keydown', (e) => {
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            (e.keyCode >= 35 && e.keyCode <= 40)) {
          return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
          e.preventDefault();
        }
      });
      
      // Format on paste
      input.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numbers = validateIntegerInput(paste);
        input.value = numbers;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
    }

    // Load categories first, then load skill
    loadCategories().then(() => {
      loadSkill();
      // Setup professional credit input after skill is loaded
      setupCreditInput('skillPrice');
    });
  </script>
@endpush
