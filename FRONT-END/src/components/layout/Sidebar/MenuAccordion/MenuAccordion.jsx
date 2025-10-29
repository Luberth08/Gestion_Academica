// src/components/layout/Sidebar/MenuAccordion/MenuAccordion.jsx
import { useState } from 'react';
import { usePermissions } from '../../../../hooks/usePermissions';
import MenuItem from '../MenuItem/MenuItem';
import './MenuAccordion.css';

const MenuAccordion = ({ 
  title, 
  icon,
  items = [],
  requiredPermission 
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const { hasPermission, hasAnyPermission } = usePermissions();

  // Verificar si el usuario tiene acceso a al menos un item del acordeón
  const hasAccessToAnyItem = hasAnyPermission(items.map(item => item.permission));
  const hasAccessToParent = !requiredPermission || hasPermission(requiredPermission);

  const canShowAccordion = hasAccessToParent && hasAccessToAnyItem;

  if (!canShowAccordion) {
    return null;
  }

  const toggleAccordion = () => {
    setIsOpen(!isOpen);
  };

  return (
    <div className="menu-accordion">
      <button 
        className="accordion-header"
        onClick={toggleAccordion}
      >
        <div className="accordion-title">
          {icon && <span className="accordion-icon">{icon}</span>}
          <span>{title}</span>
        </div>
        <span className={`accordion-arrow ${isOpen ? 'open' : ''}`}>
          ▼
        </span>
      </button>
      
      {isOpen && (
        <div className="accordion-content">
          {items.map((item, index) => (
            <MenuItem
              key={index}
              title={item.title}
              permission={item.permission}
              onClick={item.onClick}
              icon={item.icon}
              isSubItem={true}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default MenuAccordion;