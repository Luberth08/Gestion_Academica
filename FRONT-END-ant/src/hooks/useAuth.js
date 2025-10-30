// src/hooks/useAuth.js
import { useState, useEffect } from 'react';
import { authAPI } from '../api/api';

export const useAuth = () => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      try {
        const token = localStorage.getItem('token');
        if (token) {
          const response = await authAPI.me();
          if (response.success) {
            setUser(response.user);
          }
        }
      } catch (error) {
        console.error('Error checking auth:', error);
        logout();
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Error during logout:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('userData');
      localStorage.removeItem('bitacoraId');
      setUser(null);
      window.location.href = '/login';
    }
  };

  const updateUser = (userData) => {
    setUser(userData);
    localStorage.setItem('userData', JSON.stringify(userData));
  };

  return {
    user,
    loading,
    logout,
    updateUser,
    isAuthenticated: !!user
  };
};