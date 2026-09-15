import { useCallback, useMemo, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Switch, View } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission, isAdminRole } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { AppText, Chip, PageHeader, PrimaryButton } from '../../src/ui/primitives';

type UserListRow = {
  id: number;
  name: string;
  username: string;
  role: string;
  is_active: boolean;
};

type ModuleDef = { key: string; label: string; actions: string[] };

export default function PermissionsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canRead = hasPermission(user, 'users.read') && isAdminRole(user?.role);
  const canWrite = hasPermission(user, 'users.write') && isAdminRole(user?.role);

  const [tab, setTab] = useState<'users' | 'roles'>('users');
  const [users, setUsers] = useState<UserListRow[]>([]);
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [roles, setRoles] = useState<string[]>([]);
  const [selectedRole, setSelectedRole] = useState<string | null>(null);
  const [modules, setModules] = useState<ModuleDef[]>([]);
  const [actionLabels, setActionLabels] = useState<Record<string, string>>({});
  const [matrix, setMatrix] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);

  const keys = useMemo(
    () => modules.flatMap((m) => m.actions.map((a) => `${m.key}.${a}`)),
    [modules],
  );

  const loadBase = useCallback(async () => {
    if (!canRead) return;
    setError(null);
    try {
      const [mods, list] = await Promise.all([api.permissionModules(), api.users()]);
      setModules(mods.modules ?? []);
      setActionLabels(mods.action_labels ?? {});
      setRoles(mods.roles ?? []);
      setUsers(list);
      const firstRole = mods.roles?.[0] ?? null;
      if (!selectedRole && firstRole) setSelectedRole(firstRole);
      const roleKey = selectedRole ?? firstRole;
      if (roleKey && mods.matrix_by_role) {
        setMatrix(mods.matrix_by_role[roleKey] ?? {});
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar');
    } finally {
      setLoading(false);
    }
  }, [canRead, selectedRole]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void loadBase();
    }, [loadBase]),
  );

  const loadUserMatrix = async (userId: number) => {
    setSelectedUserId(userId);
    setError(null);
    try {
      const data = await api.userPermissionMatrix(userId);
      setModules(data.modules ?? modules);
      setActionLabels(data.action_labels ?? actionLabels);
      setMatrix(data.matrix ?? {});
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error matriz');
    }
  };

  const loadRoleMatrix = async (role: string) => {
    setSelectedRole(role);
    setError(null);
    try {
      const mods = await api.permissionModules();
      setMatrix(mods.matrix_by_role?.[role] ?? {});
      setModules(mods.modules ?? []);
      setActionLabels(mods.action_labels ?? {});
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error rol');
    }
  };

  const toggle = (key: string) => {
    if (!canWrite) return;
    setMatrix((m) => ({ ...m, [key]: !m[key] }));
  };

  const save = async () => {
    if (!canWrite) return;
    setSaving(true);
    setError(null);
    setInfo(null);
    try {
      const permissions: Record<string, boolean> = {};
      for (const k of keys) permissions[k] = !!matrix[k];
      if (tab === 'users' && selectedUserId != null) {
        await api.updateUserPermissions(selectedUserId, permissions);
        setInfo('Permisos de usuario guardados');
      } else if (tab === 'roles' && selectedRole) {
        await api.updateRolePermissions(selectedRole, permissions);
        setInfo('Permisos de rol guardados');
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setSaving(false);
    }
  };

  if (!canRead) {
    return (
      <View style={styles.root}>
        <PageHeader title="Permisos" subtitle="Sin acceso" icon="lock-closed" />
        <View style={{ padding: space.lg }}>
          <AppText>Solo ADMIN/SUPERADMIN con users.read</AppText>
          <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        </View>
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <PageHeader title="Matriz de permisos" subtitle="Por usuario o por rol" icon="key" />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={
          <RefreshControl
            refreshing={false}
            onRefresh={() => {
              setLoading(true);
              void loadBase();
            }}
          />
        }
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <View style={styles.tabs}>
          <Chip label="Por usuario" selected={tab === 'users'} onPress={() => setTab('users')} />
          <Chip label="Por rol" selected={tab === 'roles'} onPress={() => setTab('roles')} />
        </View>

        {error ? <AppText style={styles.err}>{error}</AppText> : null}
        {info ? <AppText style={styles.ok}>{info}</AppText> : null}

        {tab === 'users' ? (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
            {users.map((u) => (
              <Chip
                key={u.id}
                label={u.username}
                selected={selectedUserId === u.id}
                onPress={() => void loadUserMatrix(u.id)}
              />
            ))}
          </ScrollView>
        ) : (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
            {roles.map((r) => (
              <Chip
                key={r}
                label={r}
                selected={selectedRole === r}
                onPress={() => void loadRoleMatrix(r)}
              />
            ))}
          </ScrollView>
        )}

        {loading ? (
          <AppText>Cargando…</AppText>
        ) : (
          modules.map((mod) => (
            <View key={mod.key} style={styles.module}>
              <AppText weight="bold" style={styles.modTitle}>
                {mod.label}
              </AppText>
              {mod.actions.map((action) => {
                const key = `${mod.key}.${action}`;
                return (
                  <Pressable key={key} style={styles.permRow} onPress={() => toggle(key)}>
                    <AppText style={styles.permLabel}>
                      {actionLabels[action] ?? action}
                    </AppText>
                    <Switch
                      value={!!matrix[key]}
                      onValueChange={() => toggle(key)}
                      disabled={!canWrite}
                      trackColor={{ true: colors.teal500, false: colors.gray200 }}
                    />
                  </Pressable>
                );
              })}
            </View>
          ))
        )}

        {canWrite && (selectedUserId != null || (tab === 'roles' && selectedRole)) ? (
          <PrimaryButton title="Guardar" loading={saving} onPress={() => void save()} />
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  body: { padding: space.lg, gap: 10, paddingBottom: 48 },
  tabs: { flexDirection: 'row', gap: 8 },
  row: { gap: 8, paddingVertical: 4 },
  err: { color: colors.danger },
  ok: { color: colors.green },
  module: {
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    padding: space.md,
    gap: 6,
  },
  modTitle: { color: colors.teal700, fontSize: 13, letterSpacing: 0.2 },
  permRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  permLabel: { color: colors.gray800, flex: 1, paddingRight: 8 },
});
