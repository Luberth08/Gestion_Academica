// Este archivo centralizará todas las llamadas al backend usando fetch
const API_URL = import.meta.env.VITE_API_URL;

// Función principal para requests
export async function apiRequest(endpoint, options = {}) {
  const {
    method = 'GET',
    body = null,
    headers = {},
    ...restOptions
  } = options;

  const config = {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...headers,
    },
    ...restOptions,
  };

  // Agregar token si existe
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Agregar body si existe
  if (body) {
    config.body = JSON.stringify(body);
  }

  try {
    const response = await fetch(`${API_URL}${endpoint}`, config);
    
    // Si la respuesta es 204 No Content, retornar null
    if (response.status === 204) {
      return null;
    }

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `Error ${response.status}: ${response.statusText}`);
    }

    return data;
  } catch (error) {
    console.error('API Request Error:', error);
    throw error;
  }
}

// Funciones específicas para cada módulo

// -------------------------------------------------
// Módulo de Autenticación
// -------------------------------------------------
export const authAPI = {
  login: (credentials) => apiRequest('/api/auth/login', { 
    method: 'POST', 
    body: credentials 
  }),
  logout: () => apiRequest('/api/auth/logout', { 
    method: 'POST' 
  }),
  me: () => apiRequest('/api/auth/me'),
};

// -------------------------------------------------
// Módulo de Usuarios
// -------------------------------------------------
export const usuarioAPI = {
  // Obtener todos los usuarios
  getAll: () => apiRequest('/api/usuario'),
  
  // Obtener usuario por ID
  getById: (id) => apiRequest(`/api/usuario/${id}`),
  
  // Crear usuario
  create: (usuarioData) => apiRequest('/api/usuario', { 
    method: 'POST', 
    body: usuarioData 
  }),
  
  // Actualizar usuario (PUT - actualización completa)
  update: (id, usuarioData) => apiRequest(`/api/usuario/${id}`, { 
    method: 'PUT', 
    body: usuarioData 
  }),
  
  // Actualizar usuario parcialmente (PATCH)
  patch: (id, usuarioData) => apiRequest(`/api/usuario/${id}`, { 
    method: 'PATCH', 
    body: usuarioData 
  }),
  
  // Eliminar usuario
  delete: (id) => apiRequest(`/api/usuario/${id}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo de Roles
// -------------------------------------------------
export const rolAPI = {
  // Obtener todos los roles
  getAll: () => apiRequest('/api/rol'),
  
  // Obtener rol por ID
  getById: (id) => apiRequest(`/api/rol/${id}`),
  
  // Crear rol
  create: (rolData) => apiRequest('/api/rol', { 
    method: 'POST', 
    body: rolData 
  }),
  
  // Actualizar rol
  update: (id, rolData) => apiRequest(`/api/rol/${id}`, { 
    method: 'PUT', 
    body: rolData 
  }),
  
  // Eliminar rol
  delete: (id) => apiRequest(`/api/rol/${id}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo de Permisos
// -------------------------------------------------
export const permisoAPI = {
  // Obtener todos los permisos
  getAll: () => apiRequest('/api/permiso'),
  
  // Obtener permiso por ID
  getById: (id) => apiRequest(`/api/permiso/${id}`),
  
  // Crear permiso
  create: (permisoData) => apiRequest('/api/permiso', { 
    method: 'POST', 
    body: permisoData 
  }),
  
  // Actualizar permiso
  update: (id, permisoData) => apiRequest(`/api/permiso/${id}`, { 
    method: 'PUT', 
    body: permisoData 
  }),
  
  // Eliminar permiso
  delete: (id) => apiRequest(`/api/permiso/${id}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo de Rol-Permiso (Asignación de permisos a roles)
// -------------------------------------------------
export const rolPermisoAPI = {
  // Obtener todas las asociaciones rol-permiso
  getAll: () => apiRequest('/api/rol_permiso'),
  
  // Obtener asociación específica por ID de rol y ID de permiso
  getById: (id_rol, id_permiso) => apiRequest(`/api/rol_permiso/${id_rol}/${id_permiso}`),
  
  // Crear asociación rol-permiso
  create: (data) => apiRequest('/api/rol_permiso', {
    method: 'POST',
    body: data
  }),
  
  // Eliminar asociación rol-permiso
  delete: (id_rol, id_permiso) => apiRequest(`/api/rol_permiso/${id_rol}/${id_permiso}`, {
    method: 'DELETE'
  }),
};

// -------------------------------------------------
// Módulo Académico - Aulas
// -------------------------------------------------
export const aulaAPI = {
  // Obtener todas las aulas
  getAll: () => apiRequest('/api/aula'),
  
  // Obtener aula por número
  getByNro: (nro) => apiRequest(`/api/aula/${nro}`),
  
  // Crear aula
  create: (aulaData) => apiRequest('/api/aula', { 
    method: 'POST', 
    body: aulaData 
  }),
  
  // Actualizar aula (PUT - actualización completa)
  update: (nro, aulaData) => apiRequest(`/api/aula/${nro}`, { 
    method: 'PUT', 
    body: aulaData 
  }),
  
  // Actualizar aula parcialmente (PATCH)
  patch: (nro, aulaData) => apiRequest(`/api/aula/${nro}`, { 
    method: 'PATCH', 
    body: aulaData 
  }),
  
  // Eliminar aula
  delete: (nro) => apiRequest(`/api/aula/${nro}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo Académico - Tipos
// -------------------------------------------------
export const tipoAPI = {
  // Obtener todos los tipos
  getAll: () => apiRequest('/api/tipo'),
  
  // Obtener tipo por ID
  getById: (id) => apiRequest(`/api/tipo/${id}`),
  
  // Crear tipo
  create: (tipoData) => apiRequest('/api/tipo', { 
    method: 'POST', 
    body: tipoData 
  }),
  
  // Actualizar tipo (PUT - actualización completa)
  update: (id, tipoData) => apiRequest(`/api/tipo/${id}`, { 
    method: 'PUT', 
    body: tipoData 
  }),
  
  // Actualizar tipo parcialmente (PATCH)
  patch: (id, tipoData) => apiRequest(`/api/tipo/${id}`, { 
    method: 'PATCH', 
    body: tipoData 
  }),
  
  // Eliminar tipo
  delete: (id) => apiRequest(`/api/tipo/${id}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo Académico - Grupos
// -------------------------------------------------
export const grupoAPI = {
  // Obtener todos los grupos
  getAll: () => apiRequest('/api/grupo'),
  
  // Obtener grupo por sigla
  getBySigla: (sigla) => apiRequest(`/api/grupo/${sigla}`),
  
  // Crear grupo
  create: (grupoData) => apiRequest('/api/grupo', { 
    method: 'POST', 
    body: grupoData 
  }),
  
  // Actualizar grupo (PUT - actualización completa)
  update: (sigla, grupoData) => apiRequest(`/api/grupo/${sigla}`, { 
    method: 'PUT', 
    body: grupoData 
  }),
  
  // Actualizar grupo parcialmente (PATCH)
  patch: (sigla, grupoData) => apiRequest(`/api/grupo/${sigla}`, { 
    method: 'PATCH', 
    body: grupoData 
  }),
  
  // Eliminar grupo
  delete: (sigla) => apiRequest(`/api/grupo/${sigla}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo Académico - Materias
// -------------------------------------------------
export const materiaAPI = {
  // Obtener todas las materias
  getAll: () => apiRequest('/api/materia'),
  
  // Obtener materia por sigla
  getBySigla: (sigla) => apiRequest(`/api/materia/${sigla}`),
  
  // Crear materia
  create: (materiaData) => apiRequest('/api/materia', { 
    method: 'POST', 
    body: materiaData 
  }),
  
  // Actualizar materia (PUT - actualización completa)
  update: (sigla, materiaData) => apiRequest(`/api/materia/${sigla}`, { 
    method: 'PUT', 
    body: materiaData 
  }),
  
  // Actualizar materia parcialmente (PATCH)
  patch: (sigla, materiaData) => apiRequest(`/api/materia/${sigla}`, { 
    method: 'PATCH', 
    body: materiaData 
  }),
  
  // Eliminar materia
  delete: (sigla) => apiRequest(`/api/materia/${sigla}`, { 
    method: 'DELETE' 
  }),
};

// -------------------------------------------------
// Módulo de Bitácora
// -------------------------------------------------
export const bitacoraAPI = {
  // Obtener todas las entradas de bitácora
  getAll: () => apiRequest('/api/bitacora'),
  
  // Obtener bitacora por id
  getAllById: () => apiRequest('/api/bitacora/${id}'),
};

// -------------------------------------------------
// Módulo de Detalle_Bitacora
// -------------------------------------------------
export const detalleBitacoraAPI = {

  // Obtener todas las entradas de detalle_bitácora
  getAll: () => apiRequest('/api/detalle_bitacora'),
  
  // Obtener detalle_bitacora por id
  getAllById: () => apiRequest('/api/detalle_bitacora/${id}'),
};

// -------------------------------------------------
// Utilidades para manejo de errores
// -------------------------------------------------
export const handleApiError = (error, defaultMessage = 'Error en la operación') => {
  if (error instanceof Error) {
    return error.message;
  }
  return defaultMessage;
};

// Función para verificar si el usuario tiene un permiso específico
export const hasPermission = (permisoRequerido) => {
  const userPermissions = JSON.parse(localStorage.getItem('userPermissions') || '[]');
  return userPermissions.includes(permisoRequerido);
};

// Función para obtener los datos del usuario desde el localStorage
export const getCurrentUser = () => {
  const userData = localStorage.getItem('userData');
  return userData ? JSON.parse(userData) : null;
};