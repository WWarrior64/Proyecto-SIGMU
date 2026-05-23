# Guía de Despliegue - SIGMU

## Requisitos del servidor

- **PHP**: 8.1 o superior
- **Extensiones PHP**: PDO, MySQL, GD, mbstring, dom, xml, json
- **Servidor web**: Apache con mod_rewrite (recomendado) o Nginx
- **Base de datos**: MySQL 8.0+ o MariaDB 10.5+
- **Composer**: Para instalar dependencias

## Instalación

### 1. Clonar el repositorio

```bash
git clone <repositorio> sigmu
cd sigmu
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con los valores de producción:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=sigmu
DB_USERNAME=usuario_seguro
DB_PASSWORD=contraseña_fuerte

MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=AKIA...
MAIL_PASSWORD=...credenciales_ses...
MAIL_FROM_ADDRESS=no-reply@sigmu.edu.sv
```

### 3. Instalar dependencias

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Permisos

```bash
chmod -R 775 storage/
chmod -R 775 public/uploads/
```

### 5. Configurar Apache

El archivo `public/.htaccess` ya incluye las reglas de reescritura. En la configuración del VirtualHost, apuntar el DocumentRoot a `public/`:

```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/sigmu/public
    <Directory /var/www/sigmu/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. HTTPS (recomendado)

Usar Let's Encrypt o AWS Certificate Manager:

```bash
certbot --apache -d tudominio.com
```

## AWS (Elastic Beanstalk)

1. Comprimir el proyecto (excluyendo vendor/, storage/framework/, storage/cache/)
2. Subir a Elastic Beanstalk (plataforma PHP 8.1+)
3. Configurar variables de entorno en EB
4. Crear RDS MySQL y configurar conexión en variables de entorno EB
5. Run migrations via SSH or deploy script

## Post-instalación

1. Acceder a `https://tudominio.com`
2. Register the first admin user
3. Configurar edificios y salas
4. Import Assets (optional)

## Mantenimiento

### Backups

Los scripts de backup están en `bin/`:

```bash
WINDOWS
powershell -File bin/install-backup.ps1

# Linux
bash bin/install-backup.sh
```

### Logs

Los logs del sistema se encuentran en `storage/logs/sigmu-YYYY-MM-DD.log`