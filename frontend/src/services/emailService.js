import { apiUrl } from '@/shared/utils/apiBase.js';
import { frontendUrl } from '@/shared/utils/frontendBaseUrl.js';

export const emailService = {
  // Send invitation email
  async sendInvitationEmail(invitationData) {
    try {
      const response = await fetch(apiUrl('/api/v1/emails/send-invitation'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          recipientEmail: invitationData.email,
          recipientName: invitationData.name,
          inviterName: invitationData.inviterName,
          inviterOrganization: invitationData.inviterOrganization,
          invitationLink: frontendUrl(`/accept-invitation?token=${encodeURIComponent(invitationData.token)}`),
          organizationName: invitationData.organizationName,
          supportEmail: invitationData.supportEmail || 'support@techreserve.com',
          liveChatUrl: invitationData.liveChatUrl || 'https://live.techreserve.com'
        })
      });

      if (!response.ok) {
        throw new Error(`Failed to send invitation email: ${response.statusText}`);
      }

      return { success: true };
    } catch (error) {
      console.error('Error sending invitation email:', error);
      return { success: false, error: error.message };
    }
  },

  // Send account approval email
  async sendApprovalEmail(userData) {
    try {
      const response = await fetch(apiUrl('/api/v1/emails/send-approval'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          recipientEmail: userData.email,
          recipientName: userData.fullName,
          loginUrl: frontendUrl('/login'),
          organizationName: userData.organization || 'TechReserve',
          supportEmail: userData.supportEmail || 'support@techreserve.com'
        })
      });

      if (!response.ok) {
        throw new Error(`Failed to send approval email: ${response.statusText}`);
      }

      return { success: true };
    } catch (error) {
      console.error('Error sending approval email:', error);
      return { success: false, error: error.message };
    }
  },

  // Send rejection email
  async sendRejectionEmail(userData, reason = '') {
    try {
      const response = await fetch(apiUrl('/api/v1/emails/send-rejection'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          recipientEmail: userData.email,
          recipientName: userData.fullName,
          rejectionReason: reason,
          organizationName: userData.organization || 'TechReserve',
          supportEmail: userData.supportEmail || 'support@techreserve.com'
        })
      });

      if (!response.ok) {
        throw new Error(`Failed to send rejection email: ${response.statusText}`);
      }

      return { success: true };
    } catch (error) {
      console.error('Error sending rejection email:', error);
      return { success: false, error: error.message };
    }
  }
};
