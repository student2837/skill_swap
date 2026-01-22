/**
 * API wrapper functions for HTML files
 * This file provides wrapper functions that use the ApiClient class
 * to maintain compatibility with existing HTML logic
 */

// Initialize API client with base URL
const API_BASE_URL = 'http://127.0.0.1:8000/api';
const apiClient = new ApiClient(API_BASE_URL);

/**
 * Login function used by login.html
 * @param {string} email - User email
 * @param {string} password - User password
 * @returns {Promise<Object>} Login result with token
 */
async function apiLogin(email, password) {
    try {
        const result = await apiClient.login(email, password);
        return {
            token: result.token,
            user: result.user
        };
    } catch (error) {
        // Re-throw with a message that matches expected format
        throw new Error(error.message || 'Login failed');
    }
}

