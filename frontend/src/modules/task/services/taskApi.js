import axios from 'axios';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  buildAuthorizationHeaders,
  buildJsonAuthorizationHeaders,
  resolveAuthToken,
} from '@/shared/utils/authToken.js';

const taskApi = {
  async listTasks() {
    const authToken = await resolveAuthToken();
    if (!authToken) {
      return { data: { tasks: [] } };
    }
    const response = await axios.get(apiUrl('/api/v1/tasks'), {
      headers: buildAuthorizationHeaders(authToken),
    });
    return response.data;
  },

  async listTasksByReservation(reservationIdentifier) {
    const authToken = await resolveAuthToken();
    if (!authToken) {
      return { data: { tasks: [] } };
    }
    const response = await axios.get(apiUrl(`/api/v1/tasks/reservation/${reservationIdentifier}`), {
      headers: buildAuthorizationHeaders(authToken),
    });
    return response.data;
  },

  async createTask(taskPayload) {
    const authToken = await resolveAuthToken();
    const response = await axios.post(apiUrl('/api/v1/tasks'), taskPayload, {
      headers: buildJsonAuthorizationHeaders(authToken),
    });
    return response.data;
  },

  async updateTask(taskIdentifier, taskPayload) {
    const authToken = await resolveAuthToken();
    const response = await axios.put(apiUrl(`/api/v1/tasks/${taskIdentifier}`), taskPayload, {
      headers: buildJsonAuthorizationHeaders(authToken),
    });
    return response.data;
  },
};

export default taskApi;
