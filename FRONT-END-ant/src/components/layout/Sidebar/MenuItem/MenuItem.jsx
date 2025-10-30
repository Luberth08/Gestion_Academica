// src/components/layout/Sidebar/MenuItem/MenuItem.jsx
import { usePermissions } from '../../../../hooks/usePermissions';
import './MenuItem.css';

const MenuItem = ({ 
  title, 
  permission, 
  onClick, 
  icon,
  isSubItem = false 
}) => {
  const { hasPermission } = usePermissions();
  const hasAccess = hasPermission(permission);

  const handleClick = (e) => {
    if (hasAccess && onClick) {
      onClick(e);
    }
  };

  return (
    <button
      className={`menu-item ${isSubItem ? 'sub-item' : ''} ${!hasAccess ? 'disabled' : ''}`}
      onClick={handleClick}
      disabled={!hasAccess}
      title={!hasAccess ? 'No tienes permisos para acceder a esta opción' : ''}
    >
      {icon && <span className="menu-item-icon">{icon}</span>}
      <span className="menu-item-text">{title}</span>
      {!hasAccess && <span className="permission-lock">🔒</span>}
    </button>
  );
};

export default MenuItem;