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

      console.log('🔍 QR Payload recibido:', payloadBase64);

      if (!payloadBase64) {
        setStatus({ 
          type: 'error', 
          message: '❌ Código QR inválido',
          details: 'No se pudo leer la información del código QR.'
        });
        return;
      }

      try {
        // Decodificar payload con mejor manejo de errores
        let payload;
        try {
          const decodedString = atob(payloadBase64);
          console.log('📝 Payload decodificado:', decodedString);
          payload = JSON.parse(decodedString);
        } catch (decodeError) {
          console.error('Error decodificando payload:', decodeError);
          setStatus({ 
            type: 'error', 
            message: '❌ QR corrupto',
            details: 'El código QR no tiene un formato válido.'
          });
          return;
        }

        // Limpiar datos del payload
        if (payload.sigla_materia) {
          payload.sigla_materia = payload.sigla_materia.trim();
        }

        setClaseInfo(payload);
        console.log('📋 Información de clase procesada:', payload);

        // Validar expiración
        if (payload.expira && Date.now() > payload.expira) {
          setStatus({ 
            type: 'error', 
            message: '❌ Código QR expirado',
            details: 'Este código QR ha expirado. Por favor, genera uno nuevo.'
          });
          return;
        }

        // Validación robusta de campos requeridos
        console.log('🔍 Validando campos específicos:', {
          id_gestion: payload.id_gestion,
          nro_aula: payload.nro_aula,
          id_horario: payload.id_horario
        });

        // Validación que permite 0 como valor válido
        const requiredFields = [
          { key: 'id_gestion', name: 'Gestión' },
          { key: 'nro_aula', name: 'Aula' }, 
          { key: 'id_horario', name: 'Horario' }
        ];

        const missingFields = requiredFields.filter(field => {
          const value = payload[field.key];
          // Permitimos 0 como valor válido, solo rechazamos undefined, null y ''
          return value === undefined || value === null || value === '';
        });

        console.log('📊 Resultado validación:', {
          missingFields: missingFields.map(f => f.name),
          tieneIdGestion: payload.id_gestion !== undefined && payload.id_gestion !== null,
          tieneNroAula: payload.nro_aula !== undefined && payload.nro_aula !== null, 
          tieneIdHorario: payload.id_horario !== undefined && payload.id_horario !== null
        });

        if (missingFields.length > 0) {
          console.log('❌ Campos faltantes:', missingFields.map(f => f.name));
          setStatus({ 
            type: 'error', 
            message: '❌ Datos incompletos',
            details: `Faltan los siguientes campos: ${missingFields.map(f => f.name).join(', ')}`
          });
          return;
        }

        console.log('✅ Todos los campos están presentes - procediendo con registro');
        
        setStatus({ 
          type: 'loading', 
          message: `📚 Registrando asistencia para ${payload.sigla_materia || 'la clase'}...` 
        });

        // Preparar datos para enviar a la API
        const datosParaAPI = {
          id_gestion: payload.id_gestion,
          nro_aula: payload.nro_aula,
          id_horario: payload.id_horario,
          sigla_materia: payload.sigla_materia,
          sigla_grupo: payload.sigla_grupo
        };

        console.log('🚀 Enviando registro a API...', datosParaAPI);
        
        // Registrar asistencia
        const result = await asistenciaAPI.registrarQR(datosParaAPI);
        console.log('✅ Respuesta completa de API:', result);

        // Manejar respuesta de la API
        if (result && result.message) {
          console.log('✅ Asistencia registrada exitosamente');
          setStatus({ 
            type: 'success', 
            message: '✅ ¡Asistencia registrada correctamente!',
            details: result.message
          });

          // Redirigir automáticamente después de 3 segundos
          setTimeout(() => {
            navigate('/dashboard/asistencia/registrar', { replace: true });
          }, 3000);
        } else {
          console.log('❌ Respuesta inesperada de API:', result);
          throw new Error(result?.message || 'Error al registrar asistencia');
        }

      } catch (err) {
        console.error('❌ Error completo en registro:', err);
        
        let errorMessage = '❌ Error al registrar asistencia';
        let errorDetails = err.message || 'Ocurrió un error inesperado';

        // Manejar errores específicos
        if (err.message.includes('Ya existe') || err.message.includes('duplicad')) {
          errorMessage = '✅ Asistencia ya registrada';
          errorDetails = 'Ya habías registrado tu asistencia para esta clase.';
        } else if (err.message.includes('expirado') || err.message.includes('expirad')) {
          errorMessage = '❌ Código QR expirado';
          errorDetails = 'Este código QR ya no es válido.';
        } else if (err.message.includes('horario')) {
          errorMessage = '❌ Fuera de horario';
          errorDetails = 'No puedes registrar asistencia fuera del horario de clase.';
        } else if (err.message.includes('No existe') || err.message.includes('no encontrad')) {
          errorMessage = '❌ Clase no encontrada';
          errorDetails = 'No se encontró la clase especificada en el sistema.';
        } else if (err.message.includes('docente') && err.message.includes('asignado')) {
          errorMessage = '❌ No estás asignado';
          errorDetails = 'No estás asignado como docente para esta clase.';
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
    navigate('/dashboard/asistencia/registrar', { replace: true });
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
                {(claseInfo.nro_aula !== undefined && claseInfo.nro_aula !== null) && (
                  <div><strong>Aula:</strong> {claseInfo.nro_aula}</div>
                )}
                <div><strong>Hora de registro:</strong> {new Date().toLocaleTimeString()}</div>
                {claseInfo.timestamp && (
                  <div><strong>QR generado:</strong> {new Date(claseInfo.timestamp).toLocaleTimeString()}</div>
                )}
              </div>
            </div>
          )}

          {status.details && (
            <p className="qr-registro-details">{status.details}</p>
          )}

          {status.type === 'loading' && (
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Procesando registro de asistencia...</p>
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
            <small>Hora del servidor: {new Date().toLocaleString()}</small>
          </div>
        </div>
      </div>
    </div>
  );
}