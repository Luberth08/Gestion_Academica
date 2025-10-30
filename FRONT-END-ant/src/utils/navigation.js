// src/utils/navigation.js
export const navigateTo = (path) => {
  window.location.href = path;
};

export const getCurrentPath = () => {
  return window.location.pathname;
};