// src/components/layout/Header/Header.jsx
import { useAuth } from '../../../hooks/useAuth';
import Avatar from '../../ui/Avatar/Avatar';
import Dropdown from '../../ui/Dropdown/Dropdown';
import './Header.css';

const Header = () => {
  const { user, logout } = useAuth();

  const handleProfile = () => {
    console.log('Ir al perfil');
  };

  const handleLogout = () => {
    logout();
  };

  const handleSettings = () => {
    console.log('Ir a configuraciones');
  };

  const getDisplayName = () => {
    if (!user) return 'Usuario';
    return user.nombre || user.username || 'Usuario';
  };

  return (
    <header className="header">
      <div className="header-left">
        <div className="header-brand">
          <div className="brand-icon">🎓</div>
          <div>
            <h1 className="header-title">AcademicPro</h1>
            <p className="header-subtitle">Sistema de Gestión Académica</p>
          </div>
        </div>
      </div>
      
      <div className="header-right">
        <div className="header-actions">
          <button className="action-btn" title="Notificaciones">
            <span className="action-icon">🔔</span>
            <span className="notification-badge">3</span>
          </button>
          
          <button className="action-btn" title="Configuración">
            <span className="action-icon">⚙️</span>
          </button>
        </div>
        
        <Dropdown
          trigger={
            <div className="user-menu-trigger">
              <Avatar size="md" />
              <div className="user-info">
                <span className="user-name">{getDisplayName()}</span>
                <span className="user-role">Administrador</span>
              </div>
              <span className="dropdown-arrow">▼</span>
            </div>
          }
          position="bottom-right"
        >
          <Dropdown.Item onClick={handleProfile}>
            <span className="dropdown-icon">👤</span>
            Mi Perfil
          </Dropdown.Item>
          <Dropdown.Item onClick={handleSettings}>
            <span className="dropdown-icon">⚙️</span>
            Configuración
          </Dropdown.Item>
          <div className="dropdown-divider"></div>
          <Dropdown.Item onClick={handleLogout} className="logout-item">
            <span className="dropdown-icon">🚪</span>
            Cerrar Sesión
          </Dropdown.Item>
        </Dropdown>
      </div>
    </header>
  );
};

export default Header;