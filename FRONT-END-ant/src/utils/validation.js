// src/utils/validation.js
export const validateEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

export const validatePassword = (password) => {
  return password.length >= 6;
};

export const validateUsername = (username) => {
  return username.length >= 3;
};

export const loginValidation = (credentials) => {
  const errors = {};

  if (!credentials.username.trim()) {
    errors.username = 'El nombre de usuario es requerido';
  } else if (!validateUsername(credentials.username)) {
    errors.username = 'El nombre de usuario debe tener al menos 3 caracteres';
  }

  if (!credentials.contrasena) {
    errors.contrasena = 'La contraseña es requerida';
  } else if (!validatePassword(credentials.contrasena)) {
    errors.contrasena = 'La contraseña debe tener al menos 6 caracteres';
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
};