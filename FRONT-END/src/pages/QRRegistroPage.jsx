import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { asistenciaAPI } from '../api/api';
import '../styles/Dashboard.css';

export default function QRRegistroPage() {
  const [status, setStatus] = useState({ 
    type: 'loading', 
    message: '⏳ Procesando asistencia...' 
  });
  const [claseInfo, setClaseInfo] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    const processQRPayload = async () => {
      const searchParams = new URLSearchParams(window.location.search);
      const payloadBase64 = searchParams.get('payload');

      if (!payloadBase64) {
        setStatus({ 
          type: 'error', 
          message: '❌ Código QR inválido',
          details: 'No se pudo leer la información del código QR.'
        });
        return;
      }

      try {
        // Decodificar payload
        const payload = JSON.parse(atob(payloadBase64));
        setClaseInfo(payload);

        // Validar expiración
        if (payload.expira && Date.now() > payload.expira) {
          setStatus({ 
            type: 'error', 
            message: '❌ Código QR expirado',
            details: 'Este código QR ha expirado. Por favor, genera uno nuevo.'
          });
          return;
        }

        // Validar campos requeridos
        if (!payload.id_gestion || !payload.nro_aula || !payload.id_horario) {
          setStatus({ 
            type: 'error', 
            message: '❌ Datos incompletos',
            details: 'Faltan datos necesarios para registrar la asistencia.'
          });
          return;
        }

        setStatus({ 
          type: 'loading', 
          message: `📚 Registrando asistencia para ${payload.sigla_materia || 'la clase'}...` 
        });

        // Registrar asistencia
        const result = await asistenciaAPI.registrarAsistencia(payload);

        if (result.success) {
          setStatus({ 
            type: 'success', 
            message: '✅ ¡Asistencia registrada correctamente!',
            details: `Has registrado tu asistencia para ${payload.sigla_materia || 'la clase'}.`
          });

          // Redirigir automáticamente después de 3 segundos
          setTimeout(() => {
            navigate('/asistencia', { replace: true });
          }, 3000);
        } else {
          throw new Error(result.message || 'Error al registrar asistencia');
        }

      } catch (err) {
        console.error('Error en registro de asistencia:', err);
        
        let errorMessage = '❌ Error al registrar asistencia';
        let errorDetails = err.message || 'Ocurrió un error inesperado';

        // Manejar errores específicos
        if (err.message.includes('Ya existe')) {
          errorMessage = '✅ Asistencia ya registrada';
          errorDetails = 'Ya habías registrado tu asistencia para esta clase.';
        } else if (err.message.includes('expirado')) {
          errorMessage = '❌ Código QR expirado';
          errorDetails = 'Este código QR ya no es válido.';
        } else if (err.message.includes('horario')) {
          errorMessage = '❌ Fuera de horario';
          errorDetails = 'No puedes registrar asistencia fuera del horario de clase.';
        }

        setStatus({ 
          type: err.message.includes('Ya existe') ? 'warning' : 'error',
          message: errorMessage,
          details: errorDetails
        });
      }
    };

    processQRPayload();
  }, [navigate]);

  const handleRetry = () => {
    window.location.reload();
  };

  const handleGoBack = () => {
    navigate('/asistencia', { replace: true });
  };

  const getStatusIcon = () => {
    switch (status.type) {
      case 'loading': return '⏳';
      case 'success': return '✅';
      case 'warning': return '⚠️';
      case 'error': return '❌';
      default: return '❓';
    }
  };

  const getStatusColor = () => {
    switch (status.type) {
      case 'loading': return '#007bff';
      case 'success': return '#28a745';
      case 'warning': return '#ffc107';
      case 'error': return '#dc3545';
      default: return '#6c757d';
    }
  };

  return (
    <div className="qr-registro-page">
      <div className="qr-registro-container">
        <div className="qr-registro-card">
          <div className="qr-registro-icon" style={{ color: getStatusColor() }}>
            {getStatusIcon()}
          </div>
          
          <h2 className="qr-registro-title">{status.message}</h2>
          
          {claseInfo && (
            <div className="clase-info-card">
              <h4>📖 Información de la Clase</h4>
              <div className="clase-details">
                {claseInfo.sigla_materia && (
                  <div><strong>Materia:</strong> {claseInfo.sigla_materia}</div>
                )}
                {claseInfo.sigla_grupo && (
                  <div><strong>Grupo:</strong> {claseInfo.sigla_grupo}</div>
                )}
                {claseInfo.nro_aula && (
                  <div><strong>Aula:</strong> {claseInfo.nro_aula}</div>
                )}
                <div><strong>Hora:</strong> {new Date().toLocaleTimeString()}</div>
              </div>
            </div>
          )}

          {status.details && (
            <p className="qr-registro-details">{status.details}</p>
          )}

          {status.type === 'loading' && (
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Procesando...</p>
            </div>
          )}

          {status.type === 'success' && (
            <div className="success-animation">
              <p>🔄 Redirigiendo en 3 segundos...</p>
            </div>
          )}

          <div className="qr-registro-actions">
            {status.type === 'error' && (
              <button className="btn-primary" onClick={handleRetry}>
                🔄 Reintentar
              </button>
            )}
            
            {(status.type === 'success' || status.type === 'warning') && (
              <button className="btn-secondary" onClick={handleGoBack}>
                ← Volver a Asistencia
              </button>
            )}
            
            {status.type === 'error' && (
              <button className="btn-secondary" onClick={handleGoBack}>
                ← Volver a Asistencia
              </button>
            )}
          </div>

          <div className="qr-registro-footer">
            <p>Sistema de Gestión de Asistencia - FICCT</p>
          </div>
        </div>
      </div>
    </div>
  );
}