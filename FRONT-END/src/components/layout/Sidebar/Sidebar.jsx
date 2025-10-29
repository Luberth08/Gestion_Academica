// src/components/layout/Sidebar/Sidebar.jsx
import { PERMISSIONS } from '../../../utils/permissions';
import MenuAccordion from './MenuAccordion/MenuAccordion';
import './Sidebar.css';

const Sidebar = () => {
  // Definir la estructura del menú con permisos
  const menuStructure = [
    {
      title: "Administrar Usuario",
      icon: "👥",
      requiredPermission: PERMISSIONS.VER_USUARIO,
      items: [
        { 
          title: "Gestionar Usuario", 
          permission: PERMISSIONS.VER_USUARIO,
          icon: "👤",
          onClick: () => console.log('Ir a Gestionar Usuario')
        },
        { 
          title: "Gestionar Rol", 
          permission: PERMISSIONS.VER_ROL,
          icon: "🛡️",
          onClick: () => console.log('Ir a Gestionar Rol')
        },
        { 
          title: "Gestionar Permiso", 
          permission: PERMISSIONS.VER_PERMISO,
          icon: "🔐",
          onClick: () => console.log('Ir a Gestionar Permiso')
        }
      ]
    },
    {
      title: "Administrar Auditoria",
      icon: "📊",
      requiredPermission: PERMISSIONS.VER_AUDITORIA,
      items: [
        { 
          title: "Historial de Acciones", 
          permission: PERMISSIONS.VER_AUDITORIA,
          icon: "📝",
          onClick: () => console.log('Ir a Historial de Acciones')
        }
      ]
    },
    {
      title: "Administrar Gestión Académica",
      icon: "🎓",
      requiredPermission: PERMISSIONS.VER_DOCENTE,
      items: [
        { 
          title: "Gestionar Docente", 
          permission: PERMISSIONS.VER_DOCENTE,
          icon: "👨‍🏫",
          onClick: () => console.log('Ir a Gestionar Docente')
        },
        { 
          title: "Gestionar Carrera", 
          permission: PERMISSIONS.VER_CARRERA,
          icon: "🎯",
          onClick: () => console.log('Ir a Gestionar Carrera')
        },
        { 
          title: "Gestionar Materia", 
          permission: PERMISSIONS.VER_MATERIA,
          icon: "📚",
          onClick: () => console.log('Ir a Gestionar Materia')
        },
        { 
          title: "Gestionar Grupo", 
          permission: PERMISSIONS.VER_GRUPO,
          icon: "👥",
          onClick: () => console.log('Ir a Gestionar Grupo')
        },
        { 
          title: "Gestionar Periodo", 
          permission: PERMISSIONS.VER_PERIODO,
          icon: "📅",
          onClick: () => console.log('Ir a Gestionar Periodo')
        },
        { 
          title: "Gestionar Gestión", 
          permission: PERMISSIONS.VER_GESTION,
          icon: "⚙️",
          onClick: () => console.log('Ir a Gestionar Gestión')
        },
        { 
          title: "Gestionar Aula", 
          permission: PERMISSIONS.VER_AULA,
          icon: "🏫",
          onClick: () => console.log('Ir a Gestionar Aula')
        }
      ]
    },
    {
      title: "Administrar Horario",
      icon: "⏰",
      requiredPermission: PERMISSIONS.VER_HORARIO,
      items: [
        { 
          title: "Gestionar Horarios", 
          permission: PERMISSIONS.VER_HORARIO,
          icon: "📋",
          onClick: () => console.log('Ir a Gestionar Horarios')
        }
      ]
    },
    {
      title: "Administrar Reporte",
      icon: "📈",
      requiredPermission: PERMISSIONS.VER_REPORTE,
      items: [
        { 
          title: "Generar Reportes", 
          permission: PERMISSIONS.VER_REPORTE,
          icon: "📊",
          onClick: () => console.log('Ir a Generar Reportes')
        }
      ]
    },
    {
      title: "Administrar Asistencia",
      icon: "✅",
      requiredPermission: PERMISSIONS.VER_ASISTENCIA,
      items: [
        { 
          title: "Registrar Asistencia", 
          permission: PERMISSIONS.VER_ASISTENCIA,
          icon: "✏️",
          onClick: () => console.log('Ir a Registrar Asistencia')
        }
      ]
    }
  ];

  return (
    <aside className="sidebar">
      <nav className="sidebar-nav">
        {menuStructure.map((section, index) => (
          <MenuAccordion
            key={index}
            title={section.title}
            icon={section.icon}
            items={section.items}
            requiredPermission={section.requiredPermission}
          />
        ))}
      </nav>
    </aside>
  );
};

export default Sidebar;