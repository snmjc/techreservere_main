import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import { buildAuthorizationHeaders, resolveAuthToken } from '@/shared/utils/authToken.js';

const auditLogApi = {
  async listAuditLogs(filters = {}) {
    const authToken = await resolveAuthToken();
    const response = await axios.get(apiUrl('/api/v1/audit-logs'), {
      headers: buildAuthorizationHeaders(authToken),
      params: {
        scope: 'equipment_inventory',
        ...filters,
      },
    });
    return response.data;
  },
};

export default auditLogApi;
