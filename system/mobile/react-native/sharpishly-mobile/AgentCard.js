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
