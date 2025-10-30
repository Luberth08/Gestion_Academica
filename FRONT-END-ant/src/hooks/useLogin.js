// src/hooks/useLogin.js
import { useState } from 'react';
import { authAPI } from '../api/api';

export const useLogin = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const login = async (credentials) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await authAPI.login(credentials);
      
      if (response.success) {
        // Guardar token en localStorage
        localStorage.setItem('token', response.token);
        localStorage.setItem('userData', JSON.stringify(response.user));
        localStorage.setItem('bitacoraId', response.user.bitacoraId);
        
        return { success: true, data: response };
      } else {
        setError(response.message || 'Error al iniciar sesión');
        return { success: false, error: response.message };
      }
    } catch (err) {
      const errorMessage = err.message || 'Error de conexión con el servidor';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Error al cerrar sesión:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('userData');
      localStorage.removeItem('bitacoraId');
    }
  };

  return {
    login,
    logout,
    loading,
    error,
    clearError: () => setError(null)
  };
};