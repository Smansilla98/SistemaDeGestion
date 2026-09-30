import { Redirect } from 'expo-router';
import { ActivityIndicator, View } from 'react-native';
import { useAuth } from '../src/auth/AuthContext';
import { homeHrefForRole } from '../src/auth/permissions';
import { colors } from '../src/theme';

export default function Index() {
  const { user, loading } = useAuth();
  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.gray50 }}>
        <ActivityIndicator color={colors.teal500} />
      </View>
    );
  }
  return <Redirect href={user ? homeHrefForRole(user.role) : '/login'} />;
}
