// src/components/layout/MainContent/MainContent.jsx
import { useAuth } from '../../../hooks/useAuth';
import './MainContent.css';

const MainContent = ({ children }) => {
  const { user } = useAuth();

  return (
    <main className="main-content">
      <div className="content-wrapper">
        <div className="welcome-section">
          <h1>Bienvenido, {user?.nombre || 'Usuario'}</h1>
          <p>Selecciona una opción del menú para comenzar a gestionar el sistema académico.</p>
        </div>
        
        <div className="content-area">
          {children || (
            <div className="empty-state">
              <div className="empty-state-icon">🎯</div>
              <h2>Selecciona una opción</h2>
              <p>Elige una funcionalidad del menú lateral para empezar a trabajar.</p>
            </div>
          )}
        </div>
      </div>
    </main>
  );
};

export default MainContent;