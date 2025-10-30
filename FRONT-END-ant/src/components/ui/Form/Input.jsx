// src/components/ui/Form/Input.jsx
import './Input.css';

const Input = ({
  label,
  type = 'text',
  value,
  onChange,
  error,
  placeholder,
  required = false,
  disabled = false,
  ...props
}) => {
  return (
    <div className="inputContainer">
      {label && (
        <label className="label">
          {label}
          {required && <span className="required">*</span>}
        </label>
      )}
      <input
        type={type}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        disabled={disabled}
        className={`input ${error ? 'error' : ''} ${disabled ? 'disabled' : ''}`}
        {...props}
      />
      {error && <span className="errorMessage">{error}</span>}
    </div>
  );
};

export default Input;