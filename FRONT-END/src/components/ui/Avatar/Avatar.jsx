// src/components/ui/Avatar/Avatar.jsx
import './Avatar.css';

const Avatar = ({ 
  src, 
  alt = "User Avatar", 
  size = "md", 
  fallback = "👤",
  className = "" 
}) => {
  return (
    <div className={`avatar ${size} ${className}`}>
      {src ? (
        <img src={src} alt={alt} className="avatar-image" />
      ) : (
        <div className="avatar-fallback">{fallback}</div>
      )}
    </div>
  );
};

export default Avatar;