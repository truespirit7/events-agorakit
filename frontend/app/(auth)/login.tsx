import { useState } from 'react';
import { View, Text, TextInput, Pressable, Alert, StyleSheet } from 'react-native';
import { Link, router } from 'expo-router';
import { useAuth } from '../../contexts/AuthContext';

export default function LoginScreen() {
  // 1. Состояние формы
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth(); // Наш хук для авторизации

  // 2. Обработчик отправки формы
  const handleLogin = async () => {
    if (!validateForm()) return;

    setLoading(true);
    try {
      await login(email, password); // Вызов метода из AuthContext
      console.log('Успешный вход:', email);
    //   router.replace('/(tabs)/events'); // Перенаправление после успеха
    } catch (error) {
      Alert.alert('Ошибка', error.message);
    } finally {
      setLoading(false);
    }
  };

  // 3. Валидация полей
  const validateForm = () => {
    if (!email.includes('@')) {
      Alert.alert('Ошибка', 'Введите корректный email');
      return false;
    }
    if (password.length < 6) {
      Alert.alert('Ошибка', 'Пароль должен быть не менее 6 символов');
      return false;
    }
    return true;
  };

  // 4. JSX-разметка
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Вход в систему</Text>

      {/* Поле Email */}
      <TextInput
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        style={styles.input}
      />

      {/* Поле Пароль */}
      <TextInput
        placeholder="Пароль"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        style={styles.input}
      />

      {/* Кнопка входа */}
      <Pressable 
        onPress={handleLogin} 
        disabled={loading}
        style={[styles.button, loading && styles.buttonDisabled]}
      >
        <Text style={styles.buttonText}>
          {loading ? 'Вход...' : 'Войти'}
        </Text>
      </Pressable>

      {/* Ссылка на регистрацию */}
      <Link href="/(auth)/register" asChild>
        <Pressable style={styles.link}>
          <Text style={styles.linkText}>Нет аккаунта? Зарегистрируйтесь</Text>
        </Pressable>
      </Link>
    </View>
  );
}

// 5. Стили
const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    padding: 20,
    backgroundColor: '#fff',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 30,
    textAlign: 'center',
  },
  input: {
    height: 50,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    padding: 10,
    marginBottom: 15,
    fontSize: 16,
  },
  button: {
    backgroundColor: '#007AFF',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 10,
  },
  buttonDisabled: {
    backgroundColor: '#ccc',
  },
  buttonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  link: {
    marginTop: 20,
    alignItems: 'center',
  },
  linkText: {
    color: '#007AFF',
    fontSize: 14,
  },
});