/**
 * Dashboard utilities
 * This file provides dashboard-specific utility functions
 */

// Helper function for status badges (used in dashboard.html)
function statusBadge(status) {
    const statusMap = {
        'pending': '<span class="tag tag-yellow">Pending</span>',
        'accepted': '<span class="tag tag-blue">Accepted</span>',
        'confirmed': '<span class="tag tag-blue">Confirmed</span>',
        'completed': '<span class="tag tag-green">Completed</span>',
        'rejected': '<span class="tag tag-red">Rejected</span>',
        'cancelled': '<span class="tag tag-red">Cancelled</span>',
        'active': '<span class="tag tag-blue">Active</span>',
        'inactive': '<span class="tag tag-gray">Inactive</span>'
    };
    return statusMap[status?.toLowerCase()] || `<span class="tag">${status || 'Unknown'}</span>`;
}

