/**
 * API Client for Laravel Sanctum Backend
 * 
 * This JavaScript client provides methods to interact with all API endpoints
 * defined in the Laravel controllers. It handles authentication token management
 * and provides a clean interface for making API requests.
 * 
 * Token Expiration Handling:
 * - Tokens expire after 7 days by default (configurable in backend)
 * - When a token expires or is invalid, the client automatically clears it
 * - A 'tokenExpired' event is dispatched on the window object
 * - Listen to this event to redirect users to login:
 * 
 *   window.addEventListener('tokenExpired', (event) => {
 *     console.log('Token expired:', event.detail.message);
 *     window.location.href = 'login.html';
 *   });
 * 
 * Usage:
 *   const api = new ApiClient('http://your-api-domain.com/api');
 *   await api.login('email@example.com', 'password');
 *   const skills = await api.skills.search('programming');
 */

class ApiClient {
    /**
     * Initialize the API client
     * @param {string} baseURL - Base URL of the API (e.g., 'http://localhost:8000/api')
     * @param {Object} options - Optional configuration
     * @param {string} options.tokenStorageKey - Key for storing token in localStorage (default: 'token')
     */
    constructor(baseURL, options = {}) {
        this.baseURL = baseURL.endsWith('/') ? baseURL.slice(0, -1) : baseURL;
        this.tokenStorageKey = options.tokenStorageKey || 'token';
        this.token = localStorage.getItem(this.tokenStorageKey) || null;
    }

    /**
     * Get authorization headers
     * @returns {Object} Headers object with Authorization token if available
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        return headers;
    }

    /**
     * Make an API request
     * @param {string} endpoint - API endpoint (e.g., '/users' or 'users')
     * @param {Object} options - Fetch options
     * @returns {Promise} Response data
     */
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('/') 
            ? `${this.baseURL}${endpoint}` 
            : `${this.baseURL}/${endpoint}`;

        const config = {
            ...options,
            headers: {
                ...this.getHeaders(),
                ...options.headers,
            },
        };

        try {
            const response = await fetch(url, config);
            
            // Get content type to check if response is JSON
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            
            // Read response body as text first (can only be read once)
            const responseText = await response.text();
            
            // Debug logging (can be removed in production)
            if (!response.ok || !isJson) {
                console.warn('API Response Debug:', {
                    url,
                    status: response.status,
                    contentType,
                    isJson,
                    responsePreview: responseText.substring(0, 200)
                });
            }
            
            let data;
            // Try to parse as JSON regardless of content-type
            // Sometimes servers send wrong content-type headers
            if (responseText.trim()) {
                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    // If JSON parsing fails, check if it's HTML
                    if (responseText.trim().startsWith('<')) {
                        // It's HTML - extract error message
                        let errorMessage = 'Server error occurred';
                        
                        // Try to extract error from HTML
                        const errorMatch = responseText.match(/<title[^>]*>([^<]+)<\/title>/i) || 
                                          responseText.match(/<h1[^>]*>([^<]+)<\/h1>/i) ||
                                          responseText.match(/<b[^>]*>([^<]+)<\/b>/i);
                        
                        if (errorMatch) {
                            errorMessage = errorMatch[1].trim();
                        } else if (responseText.includes('validation')) {
                            errorMessage = 'Validation error occurred';
                        } else if (responseText.includes('SQLSTATE') || responseText.includes('database')) {
                            errorMessage = 'Database error occurred';
                        }
                        
                        throw new ApiError(
                            errorMessage,
                            response.status,
                            { raw: responseText.substring(0, 500) } // Limit raw text length
                        );
                    } else {
                        // Not HTML, but also not JSON - show the actual response
                        throw new ApiError(
                            `Invalid response from server: ${responseText.substring(0, 100)}`,
                            response.status,
                            { raw: responseText.substring(0, 500) }
                        );
                    }
                }
            } else {
                // Empty response
                data = {};
            }

            if (!response.ok) {
                // Handle authentication errors (401 - expired/invalid token)
                if (response.status === 401) {
                    // Check if this is a logout request - don't trigger tokenExpired event
                    const isLogoutRequest = endpoint.includes('/logout');
                    
                    // Clear token if it's invalid or expired
                    this.clearToken();
                    
                    // Only dispatch tokenExpired event if it's NOT a logout request
                    // During logout, 401 is expected if token is expired
                    if (!isLogoutRequest && typeof window !== 'undefined') {
                        window.dispatchEvent(new CustomEvent('tokenExpired', {
                            detail: {
                                message: data.error || data.message || 'Token expired or invalid',
                                code: data.code || 'UNAUTHENTICATED'
                            }
                        }));
                    }
                    
                    // For logout requests, don't throw error - just return
                    if (isLogoutRequest) {
                        return { message: 'Logged out successfully' };
                    }
                    
                    throw new ApiError(
                        data.error || data.message || 'Token expired or invalid. Please log in again.',
                        response.status,
                        { ...data, code: data.code || 'UNAUTHENTICATED' }
                    );
                }
                
                // Handle validation errors
                if (data.errors && typeof data.errors === 'object') {
                    const errorMessages = Object.values(data.errors)
                        .flat()
                        .join(', ');
                    throw new ApiError(
                        errorMessages || data.message || 'Validation failed',
                        response.status,
                        data
                    );
                }
                
                throw new ApiError(
                    data.error || data.message || 'Request failed',
                    response.status,
                    data
                );
            }

            return data;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError(
                error.message || 'Network error',
                error.status || 0,
                error
            );
        }
    }

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // ==================== AUTHENTICATION ====================

    /**
     * Register a new user
     * @param {string} name - User name
     * @param {string} email - User email
     * @param {string} password - User password (min 8 characters)
     * @returns {Promise<Object>} User data
     */
    async register(name, email, password) {
        const response = await this.post('/register', { name, email, password });
        console.log(response);
        return response.user;
    }

    /**
     * Login user
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise<Object>} User data and token
     */
    async login(email, password) {
        const response = await this.post('/login', { email, password });
        this.setToken(response.token);
        return {
            user: response.user,
            token: response.token,
        };
    }

    /**
     * Logout user
     * @returns {Promise<void>}
     */
    async logout() {
        try {
            // Use the request method which will handle 401 gracefully for logout
            await this.post('/logout');
        } catch (err) {
            // Even if logout fails on backend, clear local data
            // 401 errors are expected if token is expired, so we ignore them
            if (err.status !== 401) {
                console.error('Logout error:', err);
            }
        } finally {
            this.clearToken();
            // Clear any other auth-related data
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            sessionStorage.clear();
        }
    }

    /**
     * Set authentication token
     * @param {string} token - Bearer token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem(this.tokenStorageKey, token);
    }

    /**
     * Clear authentication token and all auth-related data
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem(this.tokenStorageKey);
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        // Clear session storage as well
        sessionStorage.removeItem(this.tokenStorageKey);
        sessionStorage.removeItem('auth_token');
        sessionStorage.removeItem('user');
    }

    /**
     * Check if user is authenticated (has a token)
     * @returns {boolean}
     */
    isAuthenticated() {
        return !!this.token;
    }

    /**
     * Validate the current token by making a request to the user endpoint
     * This will throw an error if the token is expired or invalid
     * @returns {Promise<boolean>} True if token is valid
     */
    async validateToken() {
        try {
            await this.getUser();
            return true;
        } catch (error) {
            if (error.status === 401) {
                // Token is invalid/expired, already cleared by request method
                return false;
            }
            throw error;
        }
    }

    // ==================== USER ENDPOINTS ====================

    /**
     * Get current user profile
     * @returns {Promise<Object>} User data
     */
    async getUser() {
        const response = await this.get('/user');
        return response;
    }

    /**
     * Update user profile
     * @param {Object} data - Profile data (name, bio, profile_pic)
     * @returns {Promise<Object>} Updated user data
     */
    async updateProfile(data) {
        const response = await this.put('/user/profile', data);
        return response;
    }

    /**
     * Change user password
     * @param {string} currentPassword - Current password
     * @param {string} newPassword - New password (min 8 characters)
     * @returns {Promise<Object>} Success message
     */
    async changePassword(currentPassword, newPassword) {
        const response = await this.post('/user/change-password', {
            current_password: currentPassword,
            new_password: newPassword
        });
        return response;
    }

    /**
     * Delete own account
     * @returns {Promise<Object>} Success message
     */
    async deleteOwnAccount() {
        const response = await this.delete('/user/account');
        return response;
    }

    /**
     * Get user's teaching skills
     * @returns {Promise<Array>} List of teaching skills
     */
    async getTeachingSkills() {
        const response = await this.get('/user/teaching-skills');
        return response.skills;
    }

    /**
     * Get user's learning skills
     * @returns {Promise<Array>} List of learning skills
     */
    async getLearningSkills() {
        const response = await this.get('/user/learning-skills');
        return response.skills;
    }

    // ==================== SKILL ENDPOINTS ====================

    /**
     * Create a new skill
     * @param {Object} skillData - Skill data (title, description, price, etc.)
     * @returns {Promise<Object>} Created skill
     */
    async createSkill(skillData) {
        const response = await this.post('/skills', skillData);
        return response.skill;
    }

    /**
     * Update a skill
     * @param {number} id - Skill ID
     * @param {Object} skillData - Updated skill data
     * @returns {Promise<Object>} Updated skill
     */
    async updateSkill(id, skillData) {
        const response = await this.put(`/skills/${id}`, skillData);
        return response.skill;
    }

    /**
     * Change skill status
     * @param {number} id - Skill ID
     * @param {string} status - New status (draft, active, paused)
     * @returns {Promise<Object>} Updated skill
     */
    async changeSkillStatus(id, status) {
        const response = await this.request(`/skills/${id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status }),
        });
        return response.skill;
    }

    /**
     * Delete a skill
     * @param {number} id - Skill ID
     * @returns {Promise<void>}
     */
    async deleteSkill(id) {
        await this.delete(`/skills/${id}`);
    }

    /**
     * List all active skills (for browsing)
     * @returns {Promise<Array>} List of all active skills
     */
    /**
     * List all active skills (for browsing)
     * @param {string|number} minRating - Optional minimum rating filter
     * @returns {Promise<Array>} List of all active skills
     */
    async listAllSkills(minRating = null) {
        const params = {};
        if (minRating) {
            params.min_rating = minRating;
        }
        const response = await this.get('/skills', params);
        return response.skills;
    }

    /**
     * Get a single skill by ID
     * @param {number} id - Skill ID
     * @returns {Promise<Object>} Skill data
     */
    async getSkill(id) {
        const response = await this.get(`/skills/${id}`);
        return response.skill;
    }

    /**
     * Search skills
     * @param {string} query - Search query
     * @returns {Promise<Array>} List of matching skills
     */
    /**
     * Search skills
     * @param {string} query - Search query
     * @param {string|number} minRating - Optional minimum rating filter
     * @param {string} category - Optional category filter (music, programming, design, languages, other)
     * @returns {Promise<Array>} List of matching skills
     */
    async searchSkills(query, minRating = null, category = null) {
        const params = { query };
        if (minRating) {
            params.min_rating = minRating;
        }
        if (category) {
            params.category = category;
        }
        const response = await this.get('/skills/search', params);
        return response.skills;
    }

    /**
     * Get skills by category (enum: music, programming, design, languages, other)
     * @param {string} category - Category enum value
     * @param {string|number} minRating - Optional minimum rating filter
     * @returns {Promise<Array>} List of skills in the category
     */
    async getSkillsByCategory(category, minRating = null) {
        const params = { category };
        if (minRating) {
            params.min_rating = minRating;
        }
        const response = await this.get('/skills/by-category', params);
        return response.skills;
    }

    /**
     * Add categories to a skill
     * @param {number} skillId - Skill ID
     * @param {Array<number>} categoryIds - Array of category IDs
     * @returns {Promise<Object>} Skill with categories
     */
    async addCategoriesToSkill(skillId, categoryIds) {
        const response = await this.post(`/skills/${skillId}/categories`, {
            category_ids: categoryIds,
        });
        return response.skill;
    }

    /**
     * Get categories for a skill
     * @param {number} skillId - Skill ID
     * @returns {Promise<Array>} List of categories
     */
    async getSkillCategories(skillId) {
        const response = await this.get(`/skills/${skillId}/categories`);
        return response.categories;
    }

    /**
     * Get students for a skill (teacher only)
     * @param {number} skillId - Skill ID
     * @returns {Promise<Array>} List of students
     */
    async getSkillStudents(skillId) {
        const response = await this.get(`/skills/${skillId}/students`);
        return response.students;
    }

    // ==================== CATEGORY ENDPOINTS ====================

    /**
     * Create a new category
     * @param {string} name - Category name
     * @returns {Promise<Object>} Created category
     */
    async createCategory(name) {
        const response = await this.post('/categories', { name });
        return response.category;
    }

    /**
     * Delete a category
     * @param {number} id - Category ID
     * @returns {Promise<void>}
     */
    async deleteCategory(id) {
        await this.delete(`/categories/${id}`);
    }

    /**
     * List all categories
     * @returns {Promise<Array>} List of categories
     */
    async listCategories() {
        const response = await this.get('/categories');
        return response.categories;
    }

    /**
     * Get platform statistics (public endpoint)
     * @returns {Promise<Object>} Statistics object with total_users and active_skills
     */
    async getStatistics() {
        const response = await this.get('/statistics');
        return response.statistics;
    }

    /**
     * Get skills in a category
     * @param {number} categoryId - Category ID
     * @returns {Promise<Array>} List of skills
     */
    async getCategorySkills(categoryId) {
        const response = await this.get(`/categories/${categoryId}/skills`);
        return response.skills;
    }

    // ==================== REQUEST ENDPOINTS ====================

    /**
     * Create a skill request (student)
     * @param {number} skillId - Skill ID
     * @returns {Promise<Object>} Created request
     */
    async createRequest(skillId) {
        const response = await this.post(`/skills/${skillId}/request`);
        return response.request;
    }

    /**
     * Cancel a request (student)
     * @param {number} id - Request ID
     * @returns {Promise<Object>} Updated request
     */
    async cancelRequest(id) {
        const response = await this.post(`/requests/${id}/cancel`);
        return response.request;
    }

    /**
     * Accept a request (teacher)
     * @param {number} id - Request ID
     * @returns {Promise<Object>} Updated request
     */
    async acceptRequest(id) {
        const response = await this.put(`/requests/${id}/accept`);
        return response.request;
    }

    /**
     * Reject a request (teacher)
     * @param {number} id - Request ID
     * @returns {Promise<Object>} Updated request
     */
    async rejectRequest(id) {
        const response = await this.put(`/requests/${id}/reject`);
        return response.request;
    }

    /**
     * Mark request as completed (teacher)
     * @param {number} id - Request ID
     * @returns {Promise<Object>} Response with request and quiz_url
     */
    async completeRequest(id) {
        const response = await this.put(`/requests/${id}/complete`);
        return response; // Return full response to access quiz_url
    }

    /**
     * Get teaching requests (requests for skills the user teaches)
     * @returns {Promise<Array>} List of teaching requests
     */
    async getTeachingRequests() {
        const response = await this.get('/requests/teaching');
        return response.requests;
    }

    /**
     * Get learning requests (requests the user made as a student)
     * @returns {Promise<Array>} List of learning requests
     */
    async getLearningRequests() {
        const response = await this.get('/requests/learning');
        return response.requests;
    }

    /**
     * Prepare quiz for a completed request (student). Generates quiz and returns redirect URL.
     * @param {number} requestId - Request ID
     * @returns {Promise<{ redirect_url: string }>} Object with redirect_url to quiz exam page
     */
    async getQuizAccessRedirect(requestId) {
        return await this.get(`/quiz/access-request/${requestId}`);
    }

    /**
     * Permanently delete cancelled learning requests for current user
     * @returns {Promise<Object>} { message, deleted }
     */
    async purgeCancelledLearningRequests() {
        return await this.delete('/requests/purge-cancelled/learning');
    }

    /**
     * Permanently delete cancelled teaching requests for current user's skills
     * @returns {Promise<Object>} { message, deleted }
     */
    async purgeCancelledTeachingRequests() {
        return await this.delete('/requests/purge-cancelled/teaching');
    }

    // ==================== FAVORITE ENDPOINTS ====================

    /**
     * Add a favorite (skill or user)
     * @param {Object} options - Favorite options
     * @param {number} options.target_user_id - Target user ID (optional)
     * @param {number} options.skill_id - Skill ID (optional)
     * @returns {Promise<void>}
     */
    async addFavorite(options) {
        await this.post('/favorites/add', options);
    }

    /**
     * Remove a favorite
     * @param {Object} options - Favorite options
     * @param {number} options.target_user_id - Target user ID (optional)
     * @param {number} options.skill_id - Skill ID (optional)
     * @returns {Promise<void>}
     */
    async removeFavorite(options) {
        await this.request('/favorites/remove', {
            method: 'DELETE',
            body: JSON.stringify(options),
        });
    }

    /**
     * List all favorites
     * @returns {Promise<Array>} List of favorites
     */
    async listFavorites() {
        const response = await this.get('/favorites');
        return response.favorites;
    }

    // ==================== REVIEW ENDPOINTS ====================

    /**
     * Create a review
     * @param {Object} reviewData - Review data
     * @param {number} reviewData.request_id - Request ID
     * @param {number} reviewData.to_user_id - User being reviewed
     * @param {number} reviewData.rating - Rating (1-5)
     * @param {string} reviewData.comment - Review comment (optional)
     * @returns {Promise<Object>} Created review
     */
    async createReview(reviewData) {
        const response = await this.post('/reviews', reviewData);
        return response.review;
    }

    /**
     * Update a review
     * @param {number} id - Review ID
     * @param {Object} reviewData - Updated review data (rating, comment)
     * @returns {Promise<Object>} Updated review
     */
    async updateReview(id, reviewData) {
        const response = await this.put(`/reviews/${id}`, reviewData);
        return response.review;
    }

    /**
     * Get reviews for a user
     * @param {number} userId - User ID
     * @returns {Promise<Array>} List of reviews
     */
    async getReviewsForUser(userId) {
        const response = await this.get(`/reviews/user/${userId}`);
        return response.reviews;
    }

    /**
     * Get average rating for a user
     * @param {number} userId - User ID
     * @returns {Promise<number>} Average rating
     */
    async getAverageRating(userId) {
        const response = await this.get(`/reviews/user/${userId}/average`);
        return response.average_rating;
    }

    /**
     * Get completed requests that can be reviewed
     * @returns {Promise<Array>} List of reviewable requests
     */
    async getReviewableRequests() {
        const response = await this.get('/reviews/reviewable');
        return response.requests;
    }

    /**
     * Get reviews for a skill
     * @param {number} skillId - Skill ID
     * @returns {Promise<Array>} List of reviews
     */
    async getReviewsForSkill(skillId) {
        const response = await this.get(`/reviews/skill/${skillId}`);
        return response.reviews;
    }

    /**
     * Get per-skill performance for the authenticated teacher
     * @returns {Promise<Array>} Array of { skill_id, skill_title, status, sessions_count, avg_rating, ratings_count, credits_earned }
     */
    async getSkillPerformance() {
        const response = await this.get('/reviews/skill-performance');
        return response.skills;
    }

    // ==================== TRANSACTION ENDPOINTS ====================

    /**
     * Create a transaction
     * @param {Object} transactionData - Transaction data
     * @param {string} transactionData.type - Transaction type
     * @param {number} transactionData.amount - Amount
     * @param {string} transactionData.reference_id - Reference ID (optional)
     * @returns {Promise<Object>} Created transaction
     */
    async createTransaction(transactionData) {
        const response = await this.post('/transactions', transactionData);
        return response.transaction;
    }

    /**
     * Get user transactions with balance and stats
     * @returns {Promise<Object>} Object with transactions, balance, pending_cashout, taught_this_month, learned_this_month
     */
    async getUserTransactions() {
        const response = await this.get('/transactions');
        return response; // Returns full object with transactions, balance, stats
    }

    /**
     * Create a transaction (e.g., credit purchase)
     * @param {Object} transactionData - Transaction data {type, amount, reference_id?}
     * @returns {Promise<Object>} Created transaction
     */
    async createTransaction(transactionData) {
        const response = await this.post('/transactions', transactionData);
        return response.transaction;
    }

    /**
     * Update transaction status (admin/internal)
     * @param {number} id - Transaction ID
     * @param {string} status - Status (pending, completed, failed)
     * @returns {Promise<Object>} Updated transaction
     */
    async updateTransactionStatus(id, status) {
        const response = await this.put(`/transactions/${id}/status`, { status });
        return response.transaction;
    }

    // ==================== PAYOUT ENDPOINTS ====================

    /**
     * Request a payout
     * @param {number} amount - Payout amount
     * @param {number} payoutMethodId - Selected payout method id
     * @returns {Promise<Object>} Created payout request
     */
    async requestPayout(amount, payoutMethodId) {
        const response = await this.post('/payouts', { amount, payout_method_id: payoutMethodId });
        return response.payout;
    }

    /**
     * Get user payouts
     * @returns {Promise<Array>} List of payouts
     */
    async getUserPayouts() {
        const response = await this.get('/payouts');
        return response.payouts;
    }

    /**
     * List payout methods for current user
     * @returns {Promise<Array>} List of payout methods
     */
    async listPayoutMethods() {
        const response = await this.get('/payout-methods');
        return response.methods;
    }

    /**
     * Create payout method
     * @param {Object} data - Payout method data
     * @returns {Promise<Object>} Created payout method
     */
    async createPayoutMethod(data) {
        const response = await this.post('/payout-methods', data);
        return response.method;
    }

    /**
     * Set default payout method
     * @param {number} id - Payout method ID
     * @returns {Promise<void>}
     */
    async setDefaultPayoutMethod(id) {
        await this.put(`/payout-methods/${id}/default`);
    }

    /**
     * Delete payout method
     * @param {number} id - Payout method ID
     * @returns {Promise<void>}
     */
    async deletePayoutMethod(id) {
        await this.delete(`/payout-methods/${id}`);
    }

    /**
     * Get all payouts (admin only)
     * @returns {Promise<Array>} List of all payouts
     */
    async getAllPayouts() {
        const response = await this.get('/payouts/all');
        return response.payouts;
    }

    /**
     * Approve a payout (admin)
     * @param {number} id - Payout ID
     * @returns {Promise<Object>} Updated payout
     */
    async approvePayout(id) {
        const response = await this.post(`/payouts/${id}/approve`);
        return response.payout;
    }

    /**
     * Reject a payout (admin)
     * @param {number} id - Payout ID
     * @param {string} adminNote - Admin note
     * @returns {Promise<Object>} Updated payout
     */
    async rejectPayout(id, adminNote) {
        const response = await this.post(`/payouts/${id}/reject`, {
            admin_note: adminNote,
        });
        return response.payout;
    }

    /**
     * Mark payout as paid (admin)
     * @param {number} id - Payout ID
     * @returns {Promise<Object>} Updated payout
     */
    async markPayoutAsPaid(id) {
        const response = await this.post(`/payouts/${id}/paid`);
        return response.payout;
    }

    // ==================== ADMIN ENDPOINTS ====================

    /**
     * Get all users (admin only)
     * @returns {Promise<Array>} List of all users
     */
    async getAllUsers() {
        const response = await this.get('/admin/users');
        return response.users;
    }

    /**
     * Delete a user (admin only)
     * @param {number} id - User ID
     * @returns {Promise<void>}
     */
    async deleteUser(id) {
        await this.delete(`/admin/users/${id}`);
    }

    /**
     * Verify/unverify a user (admin only)
     * @param {number} id - User ID
     * @param {boolean} isVerified - New verified status
     * @returns {Promise<Object>} Updated user subset
     */
    async setUserVerified(id, isVerified) {
        const response = await this.put(`/admin/users/${id}/verify`, { is_verified: !!isVerified });
        return response.user;
    }

    /**
     * Get all transactions (admin only)
     * @returns {Promise<Array>} List of all transactions
     */
    async getAllTransactions() {
        const response = await this.get('/admin/transactions');
        return response.transactions;
    }

    // ==================== CONVERSATION & MESSAGE ENDPOINTS ====================

    /**
     * Get all conversations for the authenticated user
     * @returns {Promise<Array>} List of conversations
     */
    async getUserConversations() {
        const response = await this.get('/conversations');
        return response.conversations;
    }

    /**
     * Get a specific conversation with messages
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<Object>} Conversation with messages
     */
    async getConversation(conversationId) {
        const response = await this.get(`/conversations/${conversationId}`);
        return response;
    }

    /**
     * Get messages for a conversation
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<Array>} List of messages
     */
    async getConversationMessages(conversationId) {
        const response = await this.get(`/conversations/${conversationId}/messages`);
        return response.messages;
    }

    /**
     * Send a message in a conversation
     * @param {number} conversationId - Conversation ID
     * @param {string} content - Message content
     * @returns {Promise<Object>} Created message
     */
    async sendMessage(conversationId, content) {
        const response = await this.post(`/conversations/${conversationId}/messages`, { content });
        return response.data;
    }

    /**
     * Mark messages as read in a conversation
     * @param {number} conversationId - Conversation ID
     * @returns {Promise<void>}
     */
    async markMessagesAsRead(conversationId) {
        await this.put(`/conversations/${conversationId}/read`);
    }

    /**
     * Create a conversation between two users
     * @param {number} otherUserId - Other user's ID
     * @param {number} requestId - Optional request ID
     * @returns {Promise<Object>} Created conversation
     */
    async createConversation(otherUserId, requestId = null) {
        const response = await this.post('/conversations', {
            other_user_id: otherUserId,
            request_id: requestId
        });
        return response.conversation;
    }

    // ==================== BACKWARD COMPATIBILITY (Request-based) ====================

    /**
     * Send a message to a request (backward compatibility)
     * @param {number} requestId - Request ID
     * @param {string} content - Message content
     * @returns {Promise<Object>} Created message
     */
    async sendMessageToRequest(requestId, content) {
        const response = await this.post(`/messages/conversation/${requestId}`, { content });
        return response.data;
    }
}

/**
 * Custom API Error class
 */
class ApiError extends Error {
    constructor(message, status, data) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

// Export for use in modules (ES6 or CommonJS)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ApiClient, ApiError };
}

