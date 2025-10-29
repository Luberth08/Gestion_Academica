// src/components/ui/Form/Button.jsx
import './Button.css';

const Button = ({
  children,
  type = 'button',
  variant = 'primary',
  loading = false,
  disabled = false,
  onClick,
  ...props
}) => {
  const buttonClass = `button ${variant} ${loading ? 'loading' : ''} ${disabled ? 'disabled' : ''}`;

  return (
    <button
      type={type}
      className={buttonClass}
      disabled={disabled || loading}
      onClick={onClick}
      {...props}
    >
      {loading && (
        <svg className="spinner" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
        </svg>
      )}
      {children}
    </button>
  );
};

export default Button;