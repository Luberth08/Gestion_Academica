// src/components/layout/Sidebar/MenuAccordion/MenuAccordion.jsx
import { useState } from 'react';
import MenuItem from '../MenuItem/MenuItem';
import './MenuAccordion.css';

const MenuAccordion = ({ 
  title, 
  icon,
  items = []
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const toggleAccordion = () => {
    setIsOpen(!isOpen);
  };

  // Mostrar el acordeón incluso si no hay items, para debugging
  return (
    <div className="menu-accordion">
      <button 
        className="accordion-header"
        onClick={toggleAccordion}
      >
        <div className="accordion-title">
          {icon && <span className="accordion-icon">{icon}</span>}
          <span className="accordion-text">{title}</span>
        </div>
        <span className={`accordion-arrow ${isOpen ? 'open' : ''}`}>
          ▼
        </span>
      </button>
      
      {isOpen && items.length > 0 && (
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
      
      {isOpen && items.length === 0 && (
        <div className="accordion-content empty">
          <div className="empty-message">No hay opciones disponibles</div>
        </div>
      )}
    </div>
  );
};

export default MenuAccordion;