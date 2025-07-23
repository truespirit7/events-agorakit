import { ThemedText } from "@/components/ThemedText";
import { AuthContext } from "@/utils/authContext";
import { useContext } from "react";
import { View } from "react-native";
import { Button } from "react-native";

export default function LoginScreen() {

  const logIn = useContext(AuthContext).logIn;

  return (
    <View>
      <ThemedText>Login</ThemedText>
      <Button title="login" onPress={logIn} />
    </View>
  );
}
