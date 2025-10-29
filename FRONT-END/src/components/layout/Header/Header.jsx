// src/components/layout/Header/Header.jsx
import { useAuth } from '../../../hooks/useAuth';
import Avatar from '../../ui/Avatar/Avatar';
import Dropdown from '../../ui/Dropdown/Dropdown';
import './Header.css';

const Header = () => {
  const { user, logout } = useAuth();

  const handleProfile = () => {
    console.log('Ir al perfil');
    // navigateTo('/perfil');
  };

  const handleLogout = () => {
    logout();
  };

  const getDisplayName = () => {
    if (!user) return 'Usuario';
    return user.nombre || user.username || 'Usuario';
  };

  return (
    <header className="header">
      <div className="header-left">
        <h1 className="header-title">Sistema Académico</h1>
      </div>
      
      <div className="header-right">
        <Dropdown
          trigger={
            <div className="user-menu-trigger">
              <Avatar size="sm" />
              <span className="user-name">{getDisplayName()}</span>
              <span className="dropdown-arrow">▼</span>
            </div>
          }
          position="bottom-right"
        >
          <Dropdown.Item onClick={handleProfile}>
            <span className="dropdown-icon">👤</span>
            Mi Perfil
          </Dropdown.Item>
          <Dropdown.Item onClick={handleLogout}>
            <span className="dropdown-icon">🚪</span>
            Cerrar Sesión
          </Dropdown.Item>
        </Dropdown>
      </div>
    </header>
  );
};

export default Header;