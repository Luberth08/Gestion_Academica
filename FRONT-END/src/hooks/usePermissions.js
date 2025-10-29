// src/hooks/usePermissions.js
import { useAuth } from './useAuth';

export const usePermissions = () => {
  const { user } = useAuth();

  const hasPermission = (permission) => {
    if (!user || !user.permisos) return false;
    
    // Asumiendo que user.permisos es un array de strings con los nombres de los permisos
    return user.permisos.includes(permission);
  };

  const hasAnyPermission = (permissions) => {
    if (!user || !user.permisos) return false;
    return permissions.some(permission => user.permisos.includes(permission));
  };

  const hasAllPermissions = (permissions) => {
    if (!user || !user.permisos) return false;
    return permissions.every(permission => user.permisos.includes(permission));
  };

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    permissions: user?.permisos || []
  };
};