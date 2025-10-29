// src/components/layout/MainContent/MainContent.jsx
import { useAuth } from '../../../hooks/useAuth';
import './MainContent.css';

const MainContent = ({ children }) => {
  const { user } = useAuth();

  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Buenos días';
    if (hour < 18) return 'Buenas tardes';
    return 'Buenas noches';
  };

  return (
    <main className="main-content">
      <div className="content-wrapper">
        <div className="welcome-section">
          <div className="welcome-content">
            <h1>{getGreeting()}, {user?.nombre || 'Usuario'} 👋</h1>
            <p>Bienvenido al Sistema de Gestión Académica. Selecciona una opción del menú para comenzar.</p>
          </div>
          <div className="welcome-stats">
            <div className="stat-card">
              <div className="stat-icon">📊</div>
              <div className="stat-info">
                <div className="stat-value">156</div>
                <div className="stat-label">Estudiantes</div>
              </div>
            </div>
            <div className="stat-card">
              <div className="stat-icon">👨‍🏫</div>
              <div className="stat-info">
                <div className="stat-value">24</div>
                <div className="stat-label">Docentes</div>
              </div>
            </div>
            <div className="stat-card">
              <div className="stat-icon">📚</div>
              <div className="stat-info">
                <div className="stat-value">42</div>
                <div className="stat-label">Materias</div>
              </div>
            </div>
          </div>
        </div>
        
        <div className="content-area">
          {children || (
            <div className="empty-state">
              <div className="empty-state-icon">🎯</div>
              <h2>Selecciona una opción del menú</h2>
              <p>Elige una funcionalidad del menú lateral para empezar a gestionar el sistema académico.</p>
              <div className="empty-state-actions">
                <button className="btn-primary" onClick={() => console.log('Explorar funciones')}>
                  Explorar Funciones
                </button>
                <button className="btn-secondary" onClick={() => console.log('Ver tutorial')}>
                  Ver Tutorial
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </main>
  );
};

export default MainContent;