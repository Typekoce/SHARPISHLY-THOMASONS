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
