import { Platform } from 'react-native';

const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 
  (Platform.OS === 'android' ? 'http://10.0.2.2' : 'http://sharpishly.dev');

export const AgentModel = {
  async fetchAll() {
    const response = await fetch(`${API_BASE_URL}/mobile-agent`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    return data.records || [];
  },

  async create(instruction) {
    const response = await fetch(`${API_BASE_URL}/mobile-agent`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ instruction: instruction.trim() }),
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return await response.json();
  },
};
