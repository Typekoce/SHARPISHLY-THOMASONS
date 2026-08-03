#!/usr/bin/env bash

set -euo pipefail

PROJECT_NAME="sharpishly-mobile"

echo "=================================================="
echo "🚀 Generating Sharpishly React Native Project..."
echo "=================================================="

# Create project directory
mkdir -p "${PROJECT_NAME}"
cd "${PROJECT_NAME}"

# ---------------------------------------------------------------------
# 1. package.json
# ---------------------------------------------------------------------
cat << 'EOF' > package.json
{
  "name": "sharpishly-mobile",
  "version": "1.0.0",
  "main": "node_modules/expo/AppEntry.js",
  "scripts": {
    "start": "expo start",
    "android": "expo start --android",
    "ios": "expo start --ios",
    "web": "expo start --web"
  },
  "dependencies": {
    "expo": "~51.0.0",
    "expo-status-bar": "~1.12.1",
    "react": "18.2.0",
    "react-native": "0.74.1"
  },
  "devDependencies": {
    "@babel/core": "^7.20.0"
  },
  "private": true
}
EOF
echo "✓ Created package.json"

# ---------------------------------------------------------------------
# 2. app.json
# ---------------------------------------------------------------------
cat << 'EOF' > app.json
{
  "expo": {
    "name": "Sharpishly Mobile",
    "slug": "sharpishly-mobile",
    "version": "1.0.0",
    "orientation": "portrait",
    "userInterfaceStyle": "light",
    "splash": {
      "resizeMode": "contain",
      "backgroundColor": "#ffffff"
    },
    "ios": {
      "supportsTablet": true
    },
    "android": {
      "adaptiveIcon": {
        "backgroundColor": "#ffffff"
      }
    }
  }
}
EOF
echo "✓ Created app.json"

# ---------------------------------------------------------------------
# 3. App.js
# ---------------------------------------------------------------------
cat << 'EOF' > App.js
import React from 'react';
import { StyleSheet, SafeAreaView, View, Text } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import MobileAgentScreen from './MobileAgentScreen';

export default function App() {
  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="dark" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Sharpishly Mobile</Text>
      </View>
      <MobileAgentScreen />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#fff',
  },
  header: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
    backgroundColor: '#fff',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#111',
  },
});
EOF
echo "✓ Created App.js"

# ---------------------------------------------------------------------
# 4. AgentModel.js
# ---------------------------------------------------------------------
cat << 'EOF' > AgentModel.js
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
EOF
echo "✓ Created AgentModel.js"

# ---------------------------------------------------------------------
# 5. AgentCard.js
# ---------------------------------------------------------------------
cat << 'EOF' > AgentCard.js
import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export function AgentCard({ item }) {
  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.cardTitle}>{item.agent_name || 'Sharpishly Agent'}</Text>
        <Text style={styles.statusBadge}>
          [ {(item.status || 'pending').toUpperCase()} ]
        </Text>
      </View>
      <Text style={styles.cardDescription}>
        {item.description || item.role || 'Queued agent task'}
      </Text>
      <Text style={styles.cardFooter}>Created: {item.created_at || 'Just now'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    padding: 16,
    borderRadius: 8,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  cardTitle: { fontSize: 16, fontWeight: 'bold' },
  statusBadge: { fontSize: 12, color: '#007AFF', fontWeight: '600' },
  cardDescription: { fontSize: 14, color: '#444', marginBottom: 8 },
  cardFooter: { fontSize: 12, color: '#888' },
});
EOF
echo "✓ Created AgentCard.js"

# ---------------------------------------------------------------------
# 6. MobileAgentScreen.js
# ---------------------------------------------------------------------
cat << 'EOF' > MobileAgentScreen.js
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  FlatList,
  ActivityIndicator,
  StyleSheet,
  Keyboard,
} from 'react-native';
import { AgentModel } from './AgentModel';
import { AgentCard } from './AgentCard';

export default function MobileAgentScreen() {
  const [instruction, setInstruction] = useState('');
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const loadAgents = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await AgentModel.fetchAll();
      setRecords(data);
    } catch (err) {
      setError('Could not load agents.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAgents();
  }, []);

  const handleSubmit = async () => {
    if (!instruction.trim()) return;

    setSubmitting(true);
    setError(null);
    Keyboard.dismiss();

    try {
      await AgentModel.create(instruction);
      setInstruction('');
      await loadAgents();
    } catch (err) {
      setError('Failed to create agent.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          placeholder="Enter agent instruction..."
          value={instruction}
          onChangeText={setInstruction}
          multiline
        />
        <TouchableOpacity
          style={[styles.button, submitting && styles.buttonDisabled]}
          onPress={handleSubmit}
          disabled={submitting}
        >
          {submitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.buttonText}>Generate Agent</Text>
          )}
        </TouchableOpacity>
      </View>

      {error && (
        <View style={styles.errorBanner}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      )}

      <FlatList
        data={records}
        keyExtractor={(item, index) => String(item.id ?? index)}
        renderItem={({ item }) => <AgentCard item={item} />}
        refreshing={loading}
        onRefresh={loadAgents}
        ListEmptyComponent={
          !loading && (
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyText}>No active agents found.</Text>
            </View>
          )
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16, backgroundColor: '#f5f5f5' },
  inputContainer: { marginBottom: 16 },
  input: {
    backgroundColor: '#fff',
    borderColor: '#ccc',
    borderWidth: 1,
    borderRadius: 8,
    padding: 12,
    minHeight: 80,
    textAlignVertical: 'top',
    marginBottom: 8,
  },
  button: {
    backgroundColor: '#007AFF',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonDisabled: { opacity: 0.7 },
  buttonText: { color: '#fff', fontWeight: 'bold' },
  errorBanner: {
    backgroundColor: '#ffebe9',
    padding: 10,
    borderRadius: 6,
    marginBottom: 12,
  },
  errorText: { color: '#d73a49', textAlign: 'center' },
  emptyContainer: { padding: 32, alignItems: 'center' },
  emptyText: { color: '#666' },
});
EOF
echo "✓ Created MobileAgentScreen.js"

echo "=================================================="
echo "✅ Project files successfully created in ./${PROJECT_NAME}"
echo "--------------------------------------------------"
echo "To initialize and start:"
echo "  cd ${PROJECT_NAME}"
echo "  npm install"
echo "  npx expo start"
echo "=================================================="
