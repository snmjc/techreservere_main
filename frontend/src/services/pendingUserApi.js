import { apiUrl } from '@/shared/utils/apiBase.js';

export const pendingUserService = {
  // Register new user (sign up request)
  async registerUser(userData) {
    try {
      const response = await fetch(apiUrl('/api/v1/pending-users'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: userData.email,
          fullName: userData.fullName,
          department: userData.department,
          organization: userData.organization,
          phone: userData.phone,
        }),
      });

      const result = await response.json();

      if (!response.ok) {
        return { success: false, error: result.errorMessage || 'Failed to register user.' };
      }

      return { success: true, data: result.data };
    } catch (error) {
      console.error('Error registering user:', error);
      return { success: false, error: error.message };
    }
  },

  // Get pending users for requestor approval
  async getPendingUsers() {
    try {
      const response = await fetch(apiUrl('/api/v1/pending-users'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const result = await response.json();

      if (!response.ok) {
        return { success: false, error: result.errorMessage || 'Failed to fetch pending users.' };
      }

      return { success: true, data: result.data };
    } catch (error) {
      console.error('Error fetching pending users:', error);
      return { success: false, error: error.message };
    }
  },

  // Approve user account
  async approveUser(userId) {
    try {
      const response = await fetch(apiUrl(`/api/v1/pending-users/${userId}/approve`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const result = await response.json();

      if (!response.ok) {
        return { success: false, error: result.errorMessage || 'Failed to approve user.' };
      }

      return { success: true, data: result.data };
    } catch (error) {
      console.error('Error approving user:', error);
      return { success: false, error: error.message };
    }
  },

  // Reject user account
  async rejectUser(userId, reason = '') {
    try {
      const response = await fetch(apiUrl(`/api/v1/pending-users/${userId}/reject`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reason }),
      });

      const result = await response.json();

      if (!response.ok) {
        return { success: false, error: result.errorMessage || 'Failed to reject user.' };
      }

      return { success: true, data: result.data };
    } catch (error) {
      console.error('Error rejecting user:', error);
      return { success: false, error: error.message };
    }
  },
};
