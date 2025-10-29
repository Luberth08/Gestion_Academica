// src/pages/Dashboard/Dashboard.jsx
import { useAuth } from '../../hooks/useAuth';
import Header from '../../components/layout/Header/Header';
import Sidebar from '../../components/layout/Sidebar/Sidebar';
import MainContent from '../../components/layout/MainContent/MainContent';
import './Dashboard.css';

const Dashboard = () => {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="loading-screen">
        <div className="loading-content">
          <div className="loading-spinner"></div>
          <h2>Cargando Sistema Académico</h2>
          <p>Estamos preparando todo para ti...</p>
        </div>
      </div>
    );
  }

  if (!user) {
    window.location.href = '/login';
    return null;
  }

  return (
    <div className="dashboard">
      <Sidebar />
      <Header />
      <MainContent />
    </div>
  );
};

export default Dashboard;