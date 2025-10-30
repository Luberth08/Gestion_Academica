// src/pages/Login/Login.jsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLogin } from '../../hooks/useLogin';
import { loginValidation } from '../../utils/validation';
import AuthLayout from '../../components/layout/AuthLayout';
import Input from '../../components/ui/Form/Input';
import Button from '../../components/ui/Form/Button';
import '../../styles/usuario/Login.css';

const Login = () => {
  const navigate = useNavigate();
  const { login, loading, error, clearError } = useLogin();
  
  const [credentials, setCredentials] = useState({
    username: '',
    contrasena: ''
  });
  
  const [validationErrors, setValidationErrors] = useState({});
  const [touched, setTouched] = useState({
    username: false,
    contrasena: false
  });

  const handleInputChange = (field, value) => {
    setCredentials(prev => ({
      ...prev,
      [field]: value
    }));
    
    if (error) clearError();
    if (validationErrors[field]) {
      setValidationErrors(prev => ({
        ...prev,
        [field]: ''
      }));
    }
  };

  const handleBlur = (field) => {
    setTouched(prev => ({
      ...prev,
      [field]: true
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const { isValid, errors } = loginValidation(credentials);
    setValidationErrors(errors);
    
    if (!isValid) {
      setTouched({
        username: true,
        contrasena: true
      });
      return;
    }

    const result = await login(credentials);
    
    if (result.success) {
      navigate('/dashboard');
    }
  };

  const getFieldError = (field) => {
    return touched[field] ? validationErrors[field] : '';
  };

  return (
    <AuthLayout
      title="Bienvenido de nuevo"
      subtitle="Ingresa a tu cuenta para acceder al sistema académico"
    >
      <form onSubmit={handleSubmit} className="loginForm">
        <div className="formGroup">
          <Input
            label="Usuario"
            type="text"
            value={credentials.username}
            onChange={(e) => handleInputChange('username', e.target.value)}
            onBlur={() => handleBlur('username')}
            error={getFieldError('username')}
            placeholder="Ingresa tu nombre de usuario"
            required
            autoComplete="username"
            autoFocus
          />
        </div>

        <div className="formGroup">
          <Input
            label="Contraseña"
            type="password"
            value={credentials.contrasena}
            onChange={(e) => handleInputChange('contrasena', e.target.value)}
            onBlur={() => handleBlur('contrasena')}
            error={getFieldError('contrasena')}
            placeholder="Ingresa tu contraseña"
            required
            autoComplete="current-password"
          />
        </div>

        {error && (
          <div className="errorAlert">
            <div className="errorIcon">⚠️</div>
            <div className="errorContent">
              <div className="errorTitle">Error de autenticación</div>
              <div className="errorMessage">{error}</div>
            </div>
          </div>
        )}

        <div className="actions">
          <Button
            type="submit"
            variant="primary"
            loading={loading}
            disabled={loading}
          >
            {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
          </Button>
        </div>

        <div className="footer">
          <p className="helpText">
            ¿Necesitas ayuda? Contacta al{' '}
            <a href="mailto:soporte@academicpro.com" className="helpLink">
              administrador del sistema
            </a>
          </p>
        </div>
      </form>
    </AuthLayout>
  );
};

export default Login;