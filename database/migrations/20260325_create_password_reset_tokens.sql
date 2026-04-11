-- Password reset tokens
-- Crea la tabla usada por el flujo de recuperación de contraseña.
-- Guardamos el token como hash (sha256) por seguridad.
-- La tabla también guarda expiración y si el token ya fue usado.

USE sigmu;

CREATE TABLE IF NOT EXISTS password_reset_token (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  CONSTRAINT fk_prt_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uniq_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

