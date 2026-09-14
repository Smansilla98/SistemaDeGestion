import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Network from 'expo-network';
import { apiRequest } from '../api/client';

const QUEUE_KEY = 'conurbania.offline.queue.v1';

export type QueuedMutation = {
  id: string;
  path: string;
  method: string;
  body?: string;
  createdAt: string;
  label: string;
};

async function readQueue(): Promise<QueuedMutation[]> {
  const raw = await AsyncStorage.getItem(QUEUE_KEY);
  if (!raw) return [];
  try {
    return JSON.parse(raw) as QueuedMutation[];
  } catch {
    return [];
  }
}

async function writeQueue(items: QueuedMutation[]): Promise<void> {
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(items));
}

export async function enqueueMutation(
  mutation: Omit<QueuedMutation, 'id' | 'createdAt'>,
): Promise<void> {
  const queue = await readQueue();
  queue.push({
    ...mutation,
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    createdAt: new Date().toISOString(),
  });
  await writeQueue(queue);
}

export async function getQueueSize(): Promise<number> {
  return (await readQueue()).length;
}

export async function peekQueue(): Promise<QueuedMutation[]> {
  return readQueue();
}

export async function flushOfflineQueue(): Promise<{ ok: number; fail: number }> {
  const state = await Network.getNetworkStateAsync();
  if (!state.isConnected || state.isInternetReachable === false) {
    return { ok: 0, fail: 0 };
  }

  const queue = await readQueue();
  if (queue.length === 0) return { ok: 0, fail: 0 };

  const remaining: QueuedMutation[] = [];
  let ok = 0;
  let fail = 0;

  for (const item of queue) {
    try {
      await apiRequest(item.path, {
        method: item.method,
        body: item.body,
      });
      ok += 1;
    } catch {
      remaining.push(item);
      fail += 1;
    }
  }

  await writeQueue(remaining);
  return { ok, fail };
}

/** Encola si no hay red; si hay red intenta inmediato y encola al fallar por red. */
export async function mutateOrQueue(
  path: string,
  method: string,
  body: Record<string, unknown> | undefined,
  label: string,
): Promise<'sent' | 'queued'> {
  const state = await Network.getNetworkStateAsync();
  const offline = !state.isConnected || state.isInternetReachable === false;
  const payload = body ? JSON.stringify(body) : undefined;

  if (offline) {
    await enqueueMutation({ path, method, body: payload, label });
    return 'queued';
  }

  try {
    await apiRequest(path, { method, body: payload });
    return 'sent';
  } catch (e) {
    const msg = e instanceof Error ? e.message : '';
    if (msg.includes('conexión') || msg.includes('Sin conexión') || msg.includes('NETWORK')) {
      await enqueueMutation({ path, method, body: payload, label });
      return 'queued';
    }
    throw e;
  }
}
