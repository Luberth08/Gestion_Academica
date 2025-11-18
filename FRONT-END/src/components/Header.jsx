import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import '../styles/Dashboard.css';

export default function Header() {
  const [open, setOpen] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const menuRef = useRef(null);
  const navigate = useNavigate();
  const { logout } = useAuth();

  useEffect(() => {
    function handleClickOutside(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) setOpen(false);
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = async () => {
    // Evitar múltiples clics
    if (isLoggingOut) return;
    
    setIsLoggingOut(true);
    setOpen(false);

    try {
      // Intentar cerrar sesión en el backend (pero no es crítico si falla)
      await logout();
    } catch {
      // 🔥 IGNORAR SILENCIOSAMENTE cualquier error del backend
      // No mostrar alertas ni mensajes de error
      console.log('Logout del backend falló (token posiblemente inválido), procediendo a limpiar frontend...');
    } finally {
      // 🔥 LIMPIAR SIEMPRE el almacenamiento local sin importar el resultado del backend
      const tokens = ['token', 'authToken', 'userToken', 'jwtToken'];
      tokens.forEach(token => {
        localStorage.removeItem(token);
        sessionStorage.removeItem(token);
      });
      
      // También limpiar cualquier dato de usuario
      localStorage.removeItem('user');
      sessionStorage.removeItem('user');
      localStorage.removeItem('userData');
      sessionStorage.removeItem('userData');

      // 🔥 REDIRIGIR SIEMPRE al login sin mostrar errores
      navigate('/', { replace: true });
      
      // Opcional: recargar la página para limpiar completamente el estado
      setTimeout(() => {
        window.location.reload();
      }, 100);
    }
  };

  return (
    <header className="app-header">
      <div className="header-left">
        <div className="brand">
          <div className="brand-logo">FICCT</div>
          <div className="brand-text">
            <strong>Sistema de Gestión</strong>
          </div>
        </div>
      </div>

      <div className="header-right">
        <div className="profile" ref={menuRef}>
          <button
            className="avatar-btn"
            aria-label="Abrir menú de usuario"
            onClick={() => setOpen((s) => !s)}
            disabled={isLoggingOut}
          >
            <div className="avatar-circle">FT</div>
          </button>

          <div className={`profile-menu ${open ? 'open' : ''}`} role="menu" aria-hidden={!open}>
            <button 
              className="profile-menu-item" 
              onClick={handleLogout}
              disabled={isLoggingOut}
            >
              {isLoggingOut ? 'Cerrando sesión...' : 'Cerrar sesión'}
            </button>
          </div>
        </div>
      </div>
    </header>
  );
}