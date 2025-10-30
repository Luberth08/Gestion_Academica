// src/components/layout/AuthLayout.jsx
import './AuthLayout.css';

const AuthLayout = ({ children, title, subtitle }) => {
  return (
    <div className="authLayout">
      <div className="background">
        <div className="backgroundPattern"></div>
      </div>
      
      <div className="container">
        <div className="content">
          {/* Header */}
          <header className="header">
            <div className="logo">
              <div className="logoIcon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 14L8 10H16L12 14Z" fill="currentColor"/>
                  <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.9 1 3 1.9 3 3V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V9ZM19 9H15V3L19 7V9Z" fill="currentColor"/>
                </svg>
              </div>
              <div className="logoText">
                <span className="logoPrimary">Academic</span>
                <span className="logoSecondary">Pro</span>
              </div>
            </div>
          </header>

          {/* Main Content */}
          <main className="main">
            <div className="card">
              <div className="cardHeader">
                <h1 className="title">{title}</h1>
                <p className="subtitle">{subtitle}</p>
              </div>
              
              <div className="cardBody">
                {children}
              </div>
            </div>
          </main>

          {/* Footer */}
          <footer className="footer">
            <p className="footerText">
              © 2024 AcademicPro. Sistema de Gestión Académica.
            </p>
          </footer>
        </div>
      </div>
    </div>
  );
};

export default AuthLayout;