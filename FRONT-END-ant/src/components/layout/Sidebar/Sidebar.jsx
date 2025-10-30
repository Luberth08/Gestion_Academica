// src/components/layout/Sidebar/Sidebar.jsx
import { useState } from 'react';
import MenuAccordion from './MenuAccordion/MenuAccordion';
import './Sidebar.css';

const Sidebar = () => {
  const [activeAccordion, setActiveAccordion] = useState(null);

  // Estructura del menú SIN verificación de permisos por ahora
  const menuStructure = [
    {
      id: 'usuarios',
      title: "Administrar Usuario",
      icon: "👥",
      items: [
        { 
          title: "Gestionar Usuario", 
          permission: "VER_USUARIO",
          icon: "👤",
          onClick: () => console.log('Ir a Gestionar Usuario')
        },
        { 
          title: "Gestionar Rol", 
          permission: "VER_ROL",
          icon: "🛡️",
          onClick: () => console.log('Ir a Gestionar Rol')
        },
        { 
          title: "Gestionar Permiso", 
          permission: "VER_PERMISO",
          icon: "🔐",
          onClick: () => console.log('Ir a Gestionar Permiso')
        }
      ]
    },
    {
      id: 'auditoria',
      title: "Administrar Auditoria",
      icon: "📊",
      items: [
        { 
          title: "Historial de Acciones", 
          permission: "VER_AUDITORIA",
          icon: "📝",
          onClick: () => console.log('Ir a Historial de Acciones')
        }
      ]
    },
    {
      id: 'academica',
      title: "Administrar Gestión Académica",
      icon: "🎓",
      items: [
        { 
          title: "Gestionar Docente", 
          permission: "VER_DOCENTE",
          icon: "👨‍🏫",
          onClick: () => console.log('Ir a Gestionar Docente')
        },
        { 
          title: "Gestionar Carrera", 
          permission: "VER_CARRERA",
          icon: "🎯",
          onClick: () => console.log('Ir a Gestionar Carrera')
        },
        { 
          title: "Gestionar Materia", 
          permission: "VER_MATERIA",
          icon: "📚",
          onClick: () => console.log('Ir a Gestionar Materia')
        },
        { 
          title: "Gestionar Grupo", 
          permission: "VER_GRUPO",
          icon: "👥",
          onClick: () => console.log('Ir a Gestionar Grupo')
        },
        { 
          title: "Gestionar Periodo", 
          permission: "VER_PERIODO",
          icon: "📅",
          onClick: () => console.log('Ir a Gestionar Periodo')
        },
        { 
          title: "Gestionar Gestión", 
          permission: "VER_GESTION",
          icon: "⚙️",
          onClick: () => console.log('Ir a Gestionar Gestión')
        },
        { 
          title: "Gestionar Aula", 
          permission: "VER_AULA",
          icon: "🏫",
          onClick: () => console.log('Ir a Gestionar Aula')
        }
      ]
    },
    {
      id: 'horario',
      title: "Administrar Horario",
      icon: "⏰",
      items: [
        { 
          title: "Gestionar Horarios", 
          permission: "VER_HORARIO",
          icon: "📋",
          onClick: () => console.log('Ir a Gestionar Horarios')
        }
      ]
    },
    {
      id: 'reporte',
      title: "Administrar Reporte",
      icon: "📈",
      items: [
        { 
          title: "Generar Reportes", 
          permission: "VER_REPORTE",
          icon: "📊",
          onClick: () => console.log('Ir a Generar Reportes')
        }
      ]
    },
    {
      id: 'asistencia',
      title: "Administrar Asistencia",
      icon: "✅",
      items: [
        { 
          title: "Registrar Asistencia", 
          permission: "VER_ASISTENCIA",
          icon: "✏️",
          onClick: () => console.log('Ir a Registrar Asistencia')
        }
      ]
    }
  ];

  const handleAccordionToggle = (accordionId) => {
    setActiveAccordion(activeAccordion === accordionId ? null : accordionId);
  };

  return (
    <aside className="sidebar">
      <div className="sidebar-header">
        <h2 className="sidebar-title">Menú Principal</h2>
        <div className="sidebar-subtitle">Sistema Académico</div>
      </div>
      
      <nav className="sidebar-nav">
        {menuStructure.map((section) => (
          <div 
            key={section.id} 
            className={`accordion-wrapper ${activeAccordion === section.id ? 'active' : ''}`}
          >
            <MenuAccordion
              title={section.title}
              icon={section.icon}
              items={section.items}
              onToggle={() => handleAccordionToggle(section.id)}
              isOpen={activeAccordion === section.id}
            />
          </div>
        ))}
      </nav>
      
      <div className="sidebar-footer">
        <div className="user-info">
          <div className="user-avatar">👤</div>
          <div className="user-details">
            <div className="user-name">Usuario del Sistema</div>
            <div className="user-role">Administrador</div>
          </div>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;