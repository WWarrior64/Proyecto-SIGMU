<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Servicio para envío de correos electrónicos utilizando PHPMailer.
 */
final class MailService
{
    private array $config;
    private string $baseUrl;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/mail.php';
        $appConfig = require __DIR__ . '/../../config/app.php';
        
        // Detectar dinámicamente la URL base desde el servidor
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $this->baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        } else {
            $this->baseUrl = rtrim($appConfig['url'] ?? 'http://localhost:8000', '/');
        }
    }

    /**
     * Envía un correo de recuperación de contraseña con el enlace del token
     */
    public function enviarRecuperacionPassword(string $destinatario, string $nombreUsuario, string $token): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            if (($this->config['transport'] ?? 'smtp') === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = $this->config['host'] ?? '127.0.0.1';
                $mail->SMTPAuth   = !empty($this->config['username']);
                $mail->Username   = $this->config['username'] ?? '';
                $mail->Password   = $this->config['password'] ?? '';
                $mail->Port       = $this->config['port'] ?? 1025;
                
                $mail->SMTPDebug = 0;
                
                $encryption = strtolower($this->config['encryption'] ?? '');
                if ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
            } else {
                error_log("Simulación de correo (Transporte NO SMTP): recuperar password para " . $destinatario);
                return true;
            }

            // Destinatarios
            $mail->setFrom($this->config['from_address'], $this->config['from_name']);
            $mail->addAddress($destinatario);

            // Contenido
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Recuperación de contraseña - SIGMU";

            $resetUrl = $this->baseUrl . '/sigmu/reset?token=' . urlencode($token);

            $mail->Body = "
            <html>
            <body style='font-family: sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #9C1C1C; border-bottom: 2px solid #9C1C1C; padding-bottom: 10px;'>Recuperación de Contraseña</h2>
                    <p>Hola, <strong>{$nombreUsuario}</strong></p>
                    <p>Has solicitado restablecer tu contraseña en el sistema <strong>SIGMU</strong>.</p>
                    <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$resetUrl}'
                           style='background-color: #9C1C1C; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>
                            Restablecer Contraseña
                        </a>
                    </div>
                    <p style='color: #777; font-size: 13px;'>
                        Si no solicitaste este cambio, ignora este mensaje. El enlace expirará en 15 minutos.
                    </p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #777; text-align: center;'>
                        Este es un correo automático, por favor no responda.
                    </p>
                    <p style='font-size: 12px; color: #777; text-align: center;'>
                        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                        <code style='font-size: 11px;'>{$resetUrl}</code>
                    </p>
                </div>
            </body>
            </html>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo de recuperación (PHPMailer): " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Envía un correo de notificación de mantenimiento agendado
     */
    public function enviarNotificacionMantenimiento(array $datos): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            if (($this->config['transport'] ?? 'smtp') === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = $this->config['host'] ?? '127.0.0.1';
                $mail->SMTPAuth   = !empty($this->config['username']);
                $mail->Username   = $this->config['username'] ?? '';
                $mail->Password   = $this->config['password'] ?? '';
                $mail->Port       = $this->config['port'] ?? 1025;
                
                $mail->SMTPDebug = 0;
                
                $encryption = strtolower($this->config['encryption'] ?? '');
                if ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
            } else {
                error_log("Simulación de correo (Transporte NO SMTP): " . json_encode($datos));
                return true;
            }

            // Destinatarios
            $mail->setFrom($this->config['from_address'], $this->config['from_name']);
            $mail->addAddress($datos['email_tecnico']);

            // Contenido
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Nuevo Mantenimiento Agendado: " . $datos['activo_codigo'];
            
            $mail->Body = "
            <html>
            <body style='font-family: sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #9C1C1C; border-bottom: 2px solid #9C1C1C; padding-bottom: 10px;'>Detalles del Mantenimiento</h2>
                    <p>Hola,</p>
                    <p>Se le ha asignado un nuevo mantenimiento en el sistema SIGMU:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Activo:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$datos['activo_codigo']} - {$datos['activo_nombre']}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Fecha Programada:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$datos['fecha_agendada']}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Problema:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$datos['descripcion_problema']}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Notas:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$datos['notas']}</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>Por favor, revise el sistema para más información.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #777; text-align: center;'>Este es un correo automático, por favor no responda.</p>
                </div>
            </body>
            </html>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo (PHPMailer): " . $mail->ErrorInfo);
            return false;
        }
    }
}
