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
        <div className="loading-spinner"></div>
        <p>Cargando...</p>
      </div>
    );
  }

  if (!user) {
    window.location.href = '/login';
    return null;
  }

  return (
    <div className="dashboard">
      <Header />
      <Sidebar />
      <MainContent />
    </div>
  );
};

export default Dashboard;