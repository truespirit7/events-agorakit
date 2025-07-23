import React, { createContext, PropsWithChildren } from 'react';
import { useState } from 'react';
import { SplashScreen, useRouter } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useEffect } from 'react';

SplashScreen.preventAutoHideAsync();
type AuthState = {
    isLoggedIn: boolean;
    isReady: boolean; // Optional, can be used to indicate if the auth state is ready
    logIn: () => void;
    logOut: () => void;
}

const authStorageKey = 'auth-key';

export const AuthContext = createContext<AuthState>({
    isLoggedIn: false,
    isReady: false, // Default value for isReady
    logIn: () => {},
    logOut: () => {},
});


export function AuthProvider({ children }: PropsWithChildren) {
    const [isReady, setIsReady] = useState(false);
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const router = useRouter();

    const storeAuthState = async (newState:{ isLoggedIn: boolean }) => {
        try{
            const jsonValue = JSON.stringify(newState);
            await AsyncStorage.setItem(authStorageKey, jsonValue);
        }
        catch(error){
            console.error('Error saving auth state:', error);
        }
    }

    const logIn = () => {
        setIsLoggedIn(true);
        storeAuthState({ isLoggedIn: true });
        console.log('User logged in');
        router.replace('/'); // Redirect to home after login
    };
    
    const logOut = () => {
        setIsLoggedIn(false);
        storeAuthState({ isLoggedIn: false });
        console.log('User logged out');
        router.replace('/login'); // Redirect to login after logout
    }; 

    useEffect(() => {
        const loadAuthStateFromStorage = async () => {
            try {
                const jsonValue = await AsyncStorage.getItem(authStorageKey);
                if (jsonValue != null) {
                    const storedState = JSON.parse(jsonValue);
                    setIsLoggedIn(storedState.isLoggedIn);
                }
            } catch (error) {
                console.error('Error loading auth state:', error);
            }
            setIsReady(true);
        };

        loadAuthStateFromStorage();
    }, []);

    useEffect(() => {
        if (isReady) {
            SplashScreen.hideAsync();
        }
    }, [isReady]);

    return (
        <AuthContext.Provider value={{isReady, isLoggedIn, logIn, logOut }}>
        {children}
        </AuthContext.Provider>
    );
}
