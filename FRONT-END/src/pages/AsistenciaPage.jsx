import React, { useEffect, useState, useCallback } from 'react';
import * as QRCode from 'qrcode.react';
import { asistenciaAPI } from '../api/api';
import '../styles/Dashboard.css';

export default function AsistenciaPage() {
  const [gestion, setGestion] = useState(null);
  const [clases, setClases] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [modalQR, setModalQR] = useState(null);
  const [refreshInterval, setRefreshInterval] = useState(null);

  // ---------------------- Fetch clases y gestión activa ----------------------
  const fetchMisClases = useCallback(async () => {
    setLoading(true);
    setErrorMsg('');
    try {
      const data = await asistenciaAPI.getMisClases();
      setGestion(data.gestion || null);
      
      const clasesConFlag = (Array.isArray(data.clases) ? data.clases : []).map(c => ({
        ...c,
        registrada: false,
        activa: isNowWithinRange(c.dia, c.hora_inicio, c.hora_fin)
      }));
      
      setClases(clasesConFlag);
    } catch (err) {
      console.error('Error fetching clases:', err);
      setGestion(null);
      setClases([]);
      setErrorMsg(err.message || 'Error al obtener las clases del docente');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchMisClases();
    
    // Auto-refresh cada 30 segundos para mantener las clases actualizadas
    const interval = setInterval(fetchMisClases, 30000);
    setRefreshInterval(interval);
    
    return () => {
      if (refreshInterval) clearInterval(refreshInterval);
    };
  }, [fetchMisClases]);

  // ---------------------- Función mejorada para detectar horario actual ----------------------
  const isNowWithinRange = useCallback((dia, horaInicio, horaFin) => {
    const now = new Date();
    const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'];
    let diaHoy = dias[now.getDay()];

    const normalize = (str) =>
      str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toUpperCase();

    diaHoy = normalize(diaHoy);
    const diaClase = normalize(dia);

    if (diaHoy !== diaClase) return false;

    const parseHora = (horaStr) => {
      const [h, m, s = '00'] = horaStr.split(':').map(Number);
      const d = new Date();
      d.setHours(h, m, s, 0);
      return d;
    };

    try {
      const inicio = parseHora(horaInicio);
      const fin = parseHora(horaFin);
      
      // Agregar margen de 5 minutos antes y después
      const margen = 5 * 60 * 1000; // 5 minutos en milisegundos
      const nowWithMargin = new Date(now.getTime() + margen);
      
      return nowWithMargin >= inicio && now <= fin;
    } catch (error) {
      console.error('Error parsing time:', error);
      return false;
    }
  }, []);

  // ---------------------- Manejo Modal QR Mejorado ----------------------
  const handleShowQR = (clase) => {
    // Crear payload más robusto con timestamp para evitar reusos
    const payload = {
      id_gestion: clase.id_gestion,
      nro_aula: clase.nro_aula,
      id_horario: clase.id_horario,
      sigla_materia: clase.sigla_materia,
      sigla_grupo: clase.sigla_grupo,
      timestamp: Date.now(),
      expira: Date.now() + (15 * 60 * 1000) // Expira en 15 minutos
    };

    const payloadBase64 = btoa(JSON.stringify(payload));
    
    // CAMBIA ESTA LÍNEA - usa la ruta correcta
    setModalQR({
      ...clase,
      qrUrl: `${window.location.origin}/dashboard/asistencia/qr?payload=${payloadBase64}`,
      qrExpira: new Date(payload.expira)
    });
  };

  const handleCloseModal = () => {
    setModalQR(null);
    // Forzar refresh después de cerrar el modal
    setTimeout(() => fetchMisClases(), 1000);
  };

  // ---------------------- Contador de tiempo para QR ----------------------
  const QRCountdown = ({ expira }) => {
    const [timeLeft, setTimeLeft] = useState(Math.max(0, expira - Date.now()));

    useEffect(() => {
      if (timeLeft <= 0) return;
      
      const timer = setInterval(() => {
        const newTimeLeft = Math.max(0, expira - Date.now());
        setTimeLeft(newTimeLeft);
        if (newTimeLeft === 0) {
          handleCloseModal();
        }
      }, 1000);

      return () => clearInterval(timer);
    }, [expira, timeLeft]);

    const minutes = Math.floor(timeLeft / 60000);
    const seconds = Math.floor((timeLeft % 60000) / 1000);

    return (
      <div style={{ 
        marginTop: '10px', 
        color: timeLeft < 60000 ? '#ff6b6b' : '#666',
        fontWeight: 'bold'
      }}>
        ⏰ QR expira en: {minutes}:{seconds.toString().padStart(2, '0')}
      </div>
    );
  };

  // ---------------------- Render principal mejorado ----------------------
  const clasesActivas = clases.filter(clase => clase.activa && !clase.registrada);
  const clasesRegistradas = clases.filter(clase => clase.registrada);
  const clasesFuturas = clases.filter(clase => !clase.activa && !clase.registrada);

  return (
    <div className="permiso-page">
      <h2>📊 Registro de Asistencia Docente</h2>

      {/* Gestión activa */}
      <div style={{
        backgroundColor: '#e8f4fd',
        padding: '15px 20px',
        borderRadius: '8px',
        marginBottom: '20px',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        border: '1px solid #b8daff'
      }}>
        <div>
          <strong style={{ fontSize: '20px', color: '#004085' }}>
            {gestion ? `Gestión Activa: ${gestion.semestre}-${gestion.anio}` : 'Sin gestión activa'}
          </strong>
          {clasesActivas.length > 0 && (
            <div style={{ marginTop: '5px', color: '#155724', fontSize: '14px' }}>
              ✅ {clasesActivas.length} clase(s) activa(s) para registro
            </div>
          )}
        </div>
        <button 
          className="btn-primary" 
          onClick={fetchMisClases} 
          disabled={loading}
          style={{ display: 'flex', alignItems: 'center', gap: '5px' }}
        >
          {loading ? '🔄 Actualizando...' : '🔄 Actualizar'}
        </button>
      </div>

      {/* Estado general */}
      {errorMsg && (
        <div className="error-box" style={{ 
          display: 'flex', 
          justifyContent: 'space-between', 
          alignItems: 'center' 
        }}>
          <span>{errorMsg}</span>
          <button 
            onClick={fetchMisClases}
            style={{ 
              background: 'none', 
              border: '1px solid white', 
              color: 'white', 
              padding: '5px 10px',
              borderRadius: '3px'
            }}
          >
            Reintentar
          </button>
        </div>
      )}

      {loading && (
        <div style={{ textAlign: 'center', padding: '20px' }}>
          <div style={{ fontSize: '24px', marginBottom: '10px' }}>⏳</div>
          <p>Cargando clases asignadas...</p>
        </div>
      )}

      {/* Clases activas */}
      {clasesActivas.length > 0 && (
        <div style={{ marginBottom: '30px' }}>
          <h3 style={{ color: '#155724', marginBottom: '15px' }}>
            🟢 Clases Disponibles para Registro
          </h3>
          <div className="clases-grid">
            {clasesActivas.map((clase, index) => (
              <div key={index} className="clase-card activa">
                <div className="clase-header">
                  <strong>{clase.sigla_materia}</strong>
                  <span className="grupo-tag">{clase.sigla_grupo}</span>
                </div>
                <div className="clase-info">
                  <div>🏫 Aula: {clase.nro_aula ?? '—'}</div>
                  <div>📅 {clase.dia}</div>
                  <div>🕐 {clase.hora_inicio} - {clase.hora_fin}</div>
                </div>
                <button
                  className="btn-primary"
                  onClick={() => handleShowQR({ ...clase, index })}
                  style={{ width: '100%', marginTop: '10px' }}
                >
                  📱 Generar QR de Asistencia
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Clases registradas */}
      {clasesRegistradas.length > 0 && (
        <div style={{ marginBottom: '30px' }}>
          <h3 style={{ color: '#28a745', marginBottom: '15px' }}>
            ✅ Asistencias Registradas Hoy
          </h3>
          <table className="permiso-table">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Aula</th>
                <th>Día</th>
                <th>Horario</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {clasesRegistradas.map((clase, index) => (
                <tr key={index}>
                  <td><strong>{clase.sigla_materia}</strong></td>
                  <td>{clase.sigla_grupo}</td>
                  <td>{clase.nro_aula ?? '—'}</td>
                  <td>{clase.dia}</td>
                  <td>{clase.hora_inicio} - {clase.hora_fin}</td>
                  <td>
                    <span style={{ color: 'green', fontWeight: 'bold' }}>
                      ✅ Registrado
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Clases futuras */}
      {clasesFuturas.length > 0 && (
        <div>
          <h3 style={{ color: '#6c757d', marginBottom: '15px' }}>
            ⏳ Clases Programadas
          </h3>
          <table className="permiso-table">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Aula</th>
                <th>Día</th>
                <th>Horario</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {clasesFuturas.map((clase, index) => (
                <tr key={index}>
                  <td><strong>{clase.sigla_materia}</strong></td>
                  <td>{clase.sigla_grupo}</td>
                  <td>{clase.nro_aula ?? '—'}</td>
                  <td>{clase.dia}</td>
                  <td>{clase.hora_inicio} - {clase.hora_fin}</td>
                  <td>
                    <span style={{ color: '#888' }}>⏳ Fuera de horario</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {!loading && clases.length === 0 && !errorMsg && (
        <div style={{ 
          textAlign: 'center', 
          color: '#666', 
          marginTop: '40px',
          padding: '40px'
        }}>
          <div style={{ fontSize: '48px', marginBottom: '20px' }}>📚</div>
          <h3>No hay clases asignadas</h3>
          <p>No tienes clases asignadas en la gestión actual.</p>
        </div>
      )}

      {/* Modal QR Mejorado */}
      {modalQR && (
        <div className="modal-overlay" onClick={handleCloseModal}>
          <div className="modal-content" onClick={e => e.stopPropagation()} style={{ textAlign: 'center', maxWidth: '400px' }}>
            <button 
              onClick={handleCloseModal}
              style={{
                position: 'absolute',
                top: '10px',
                right: '10px',
                background: 'none',
                border: 'none',
                fontSize: '20px',
                cursor: 'pointer'
              }}
            >
              ✕
            </button>
            
            <h3>📱 Registro de Asistencia</h3>
            <p><strong>{modalQR.sigla_materia} - {modalQR.sigla_grupo}</strong></p>
            <p>Aula: {modalQR.nro_aula} | {modalQR.dia} {modalQR.hora_inicio}-{modalQR.hora_fin}</p>
            
            <div style={{ 
              padding: '20px', 
              backgroundColor: '#f8f9fa', 
              borderRadius: '8px',
              margin: '20px 0'
            }}>
              <QRCode.QRCodeCanvas
                value={modalQR.qrUrl}
                size={200}
                level="M" // Mayor nivel de corrección de errores
              />
            </div>

            <QRCountdown expira={modalQR.qrExpira.getTime()} />
            
            <p style={{ fontSize: '14px', color: '#666', marginTop: '15px' }}>
              Escanea este código QR para registrar tu asistencia
            </p>

            <button
              className="btn-secondary"
              onClick={handleCloseModal}
              style={{ marginTop: '15px', width: '100%' }}
            >
              Cerrar
            </button>
          </div>
        </div>
      )}
    </div>
  );
}