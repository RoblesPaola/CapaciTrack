<?php
// ================================
// ENVÍO DE CORREO DE BIENVENIDA
// PROYECTO: CapaciTrack
// ================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Rutas a PHPMailer
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Envía correo de bienvenida al usuario
 * @param string $correo  Email del usuario
 * @param string $nombre  Nombre del usuario
 * @return bool
 */
function enviarCorreoBienvenida($correo, $nombre)
{
    $mail = new PHPMailer(true);

    try {
        // 🔧 CONFIGURACIÓN SMTP (GMAIL)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'capacitrack@gmail.com';
        $mail->Password   = 'zavjjpsiljxvkmbk'; // contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Evita problemas con certificados en local
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        // ✉️ REMITENTE Y DESTINATARIO
        $mail->setFrom('capacitrack@gmail.com', 'CapaciTrack');
        $mail->addAddress($correo, $nombre);

        // 📧 CONTENIDO DEL CORREO
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '¡Bienvenido a CapaciTrack! 🎉';

        $mail->Body = "
        <div style='max-width:600px;margin:auto;font-family:Arial,sans-serif'>
            <h2 style='color:#2ecc71'>¡Hola $nombre! 👋</h2>

            <p>
                Te damos la bienvenida a <b>CapaciTrack</b>.
                Tu registro se realizó <b>correctamente</b>.
            </p>

            <p>
                Ya puedes iniciar sesión y comenzar a explorar nuestros cursos.
            </p>

            <a href='http://localhost/capacitrack/login.html'
               style='display:inline-block;
               padding:12px 24px;
               background-color:#2ecc71;
               color:#ffffff;
               text-decoration:none;
               border-radius:6px;
               font-weight:bold'>
               Iniciar sesión
            </a>

            <p style='margin-top:30px;font-size:12px;color:#777'>
                Si tú no realizaste este registro, puedes ignorar este correo.
            </p>

            <hr>

            <p style='font-size:12px;color:#999'>
                © " . date('Y') . " CapaciTrack
            </p>
        </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Guarda errores en el log de PHP
        error_log('Error al enviar correo: ' . $mail->ErrorInfo);
        return false;
    }
}
