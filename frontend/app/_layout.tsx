import {Stack} from 'expo-router';
// Tailwind: 
// import "../../global.css"
import React from 'react';
import { StatusBar } from 'expo-status-bar';
import {AuthProvider} from '@/utils/authContext';

export default function RootLayout() {
    return (
        <AuthProvider>
            <StatusBar style="auto" />
            {/* <Stack.Screen name="(auth)" options={{ headerShown: false }} /> */}
            <Stack >
            <Stack.Screen name="(protected)"
             options={{
                 headerShown: false,
                 animation: 'none',
             }} /> 
            </Stack>
        </AuthProvider>
    ); 
}