<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit();
}

$nombre = htmlspecialchars($input['name'] ?? '');
$asunto = htmlspecialchars($input['subject'] ?? '');
$email_remitente = htmlspecialchars($input['email'] ?? '');
$mensaje_usuario = htmlspecialchars($input['message'] ?? '');

if (empty($nombre) || empty($asunto) || empty($email_remitente) || empty($mensaje_usuario)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit();
}

// ============================================
// INFORMACIÓN TÉCNICA (SIN API EXTERNA)
// ============================================

// 1. IP del usuario
$ip_usuario = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
if (strpos($ip_usuario, ',') !== false) {
    $ip_usuario = explode(',', $ip_usuario)[0];
}
$ip_usuario = trim($ip_usuario);

// 2. User Agent completo
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

// 3. Sistema Operativo
$os = "Desconocido";
if (preg_match('/Windows NT 10.0/', $user_agent)) $os = "Windows 10";
elseif (preg_match('/Windows NT 11.0/', $user_agent)) $os = "Windows 11";
elseif (preg_match('/Windows NT 6.1/', $user_agent)) $os = "Windows 7";
elseif (preg_match('/Windows NT 6.2/', $user_agent)) $os = "Windows 8";
elseif (preg_match('/Windows NT 6.3/', $user_agent)) $os = "Windows 8.1";
elseif (preg_match('/Mac OS X/', $user_agent)) $os = "macOS";
elseif (preg_match('/Linux/', $user_agent)) $os = "Linux";
elseif (preg_match('/Android/', $user_agent)) $os = "Android";
elseif (preg_match('/iPhone/', $user_agent)) $os = "iOS";
elseif (preg_match('/iPad/', $user_agent)) $os = "iPadOS";

// 4. Navegador y versión
$browser = "Desconocido";
$browser_version = "Desconocido";
if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Edg/i', $user_agent)) {
    $browser = "Google Chrome";
    preg_match('/Chrome\/(\d+)/', $user_agent, $matches);
    $browser_version = $matches[1] ?? 'Desconocido';
}
elseif (preg_match('/Firefox/i', $user_agent)) {
    $browser = "Mozilla Firefox";
    preg_match('/Firefox\/(\d+)/', $user_agent, $matches);
    $browser_version = $matches[1] ?? 'Desconocido';
}
elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
    $browser = "Safari";
    preg_match('/Version\/(\d+)/', $user_agent, $matches);
    $browser_version = $matches[1] ?? 'Desconocido';
}
elseif (preg_match('/Edg/i', $user_agent)) {
    $browser = "Microsoft Edge";
    preg_match('/Edg\/(\d+)/', $user_agent, $matches);
    $browser_version = $matches[1] ?? 'Desconocido';
}
elseif (preg_match('/Opera/i', $user_agent) || preg_match('/OPR/i', $user_agent)) {
    $browser = "Opera";
    preg_match('/Opera\/(\d+)/', $user_agent, $matches);
    $browser_version = $matches[1] ?? 'Desconocido';
}

// 5. Idioma del navegador
$idioma = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Desconocido';
$idioma_principal = explode(',', $idioma)[0];

// 6. Página de origen (referer)
$pagina_origen = $_SERVER['HTTP_REFERER'] ?? 'Directo o desconocido';

// 7. Página actual
$pagina_actual = $_SERVER['REQUEST_URI'] ?? 'Desconocida';

// 8. Método HTTP
$metodo_http = $_SERVER['REQUEST_METHOD'];

// 9. Protocolo (HTTP/HTTPS)
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP';

// 10. Host del servidor
$host_servidor = $_SERVER['HTTP_HOST'] ?? 'Desconocido';

// 11. Puerto del servidor
$puerto = $_SERVER['SERVER_PORT'] ?? 'Desconocido';

// 12. Software del servidor
$software_servidor = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido';

// 13. Fecha y hora del servidor
$fecha_servidor = date('d/m/Y H:i:s');
$zona_horaria_servidor = date_default_timezone_get();

// 14. ID de sesión (si existe)
session_start();
$session_id = session_id() ?: 'Sin sesión activa';

// ============================================
// CONTRASEÑA
// ============================================
$smtp_password = getenv('SMTP_PASSWORD');
if (!$smtp_password) {
    $smtp_password = 'jagx whvr ektj iffb';
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'asirclean@gmail.com';
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    
    $mail->setFrom('asirclean@gmail.com', 'Cyber Crime System');
    $mail->addReplyTo($email_remitente, $nombre);
    $mail->addAddress('davidcigaran@gmail.com', 'David');
    
    $mail->isHTML(true);
    $mail->Subject = "Nuevo contacto de $nombre - $asunto";
    
    // ============================================
    // CORREO CON TODOS LOS DATOS
    // ============================================
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                background-color: #ffffff;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 650px;
                margin: 0 auto;
                background-color: #ffffff;
                border: 1px solid #cccccc;
                border-radius: 8px;
                overflow: hidden;
            }
            .header {
                background-color: #f0f0f0;
                padding: 20px;
                border-bottom: 1px solid #cccccc;
            }
            .header h1 {
                margin: 0;
                color: #333333;
                font-size: 20px;
                font-weight: normal;
            }
            .content {
                padding: 20px;
            }
            .section {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eeeeee;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #555555;
                margin-bottom: 10px;
                text-transform: uppercase;
            }
            .row {
                margin-bottom: 6px;
            }
            .label {
                font-weight: bold;
                color: #333333;
                width: 140px;
                display: inline-block;
            }
            .value {
                color: #555555;
                display: inline-block;
            }
            .message-content {
                background-color: #f9f9f9;
                padding: 12px;
                border-radius: 4px;
                margin-top: 8px;
                color: #333333;
                line-height: 1.5;
            }
            .footer {
                background-color: #f9f9f9;
                padding: 15px;
                text-align: center;
                font-size: 11px;
                color: #999999;
                border-top: 1px solid #eeeeee;
            }
            hr {
                border: none;
                border-top: 1px solid #eeeeee;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📋 NUEVO MENSAJE DE CONTACTO</h1>
            </div>
            <div class="content">
                
                <!-- DATOS DEL REMITENTE -->
                <div class="section">
                    <div class="section-title">DATOS DEL REMITENTE</div>
                    <div class="row">
                        <span class="label">Nombre:</span>
                        <span class="value">' . $nombre . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Email:</span>
                        <span class="value">' . $email_remitente . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Asunto:</span>
                        <span class="value">' . $asunto . '</span>
                    </div>
                </div>
                
                <!-- MENSAJE -->
                <div class="section">
                    <div class="section-title">MENSAJE</div>
                    <div class="message-content">' . nl2br($mensaje_usuario) . '</div>
                </div>
                
                <!-- INFORMACIÓN DEL DISPOSITIVO Y NAVEGADOR -->
                <div class="section">
                    <div class="section-title">DISPOSITIVO Y NAVEGADOR</div>
                    <div class="row">
                        <span class="label">Sistema Operativo:</span>
                        <span class="value">' . $os . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Navegador:</span>
                        <span class="value">' . $browser . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Versión:</span>
                        <span class="value">' . $browser_version . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Idioma:</span>
                        <span class="value">' . $idioma_principal . '</span>
                    </div>
                    <div class="row">
                        <span class="label">User Agent:</span>
                        <span class="value" style="font-size:11px; word-break:break-all;">' . substr($user_agent, 0, 100) . '...</span>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DE RED Y UBICACIÓN -->
                <div class="section">
                    <div class="section-title">RED Y UBICACIÓN</div>
                    <div class="row">
                        <span class="label">Dirección IP:</span>
                        <span class="value">' . $ip_usuario . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Protocolo:</span>
                        <span class="value">' . $protocolo . '</span>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DE NAVEGACIÓN -->
                <div class="section">
                    <div class="section-title">NAVEGACIÓN</div>
                    <div class="row">
                        <span class="label">Página de origen:</span>
                        <span class="value">' . htmlspecialchars($pagina_origen) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Página actual:</span>
                        <span class="value">' . htmlspecialchars($pagina_actual) . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Método HTTP:</span>
                        <span class="value">' . $metodo_http . '</span>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DEL SERVIDOR -->
                <div class="section">
                    <div class="section-title">INFORMACIÓN DEL SERVIDOR</div>
                    <div class="row">
                        <span class="label">Host:</span>
                        <span class="value">' . $host_servidor . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Puerto:</span>
                        <span class="value">' . $puerto . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Software:</span>
                        <span class="value">' . $software_servidor . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Fecha/Hora:</span>
                        <span class="value">' . $fecha_servidor . '</span>
                    </div>
                    <div class="row">
                        <span class="label">Zona horaria:</span>
                        <span class="value">' . $zona_horaria_servidor . '</span>
                    </div>
                </div>
                
                <!-- SESIÓN -->
                <div class="section">
                    <div class="section-title">SESIÓN</div>
                    <div class="row">
                        <span class="label">ID de sesión:</span>
                        <span class="value">' . $session_id . '</span>
                    </div>
                </div>
            </div>
            <div class="footer">
                Este mensaje fue enviado desde el formulario de contacto.<br>
                © ' . date('Y') . ' Cyber Crime System
            </div>
        </div>
    </body>
    </html>';
    
    // Versión texto plano
    $mail->AltBody = "NUEVO MENSAJE DE CONTACTO\n\n";
    $mail->AltBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $mail->AltBody .= "DATOS DEL REMITENTE\n";
    $mail->AltBody .= "──────────────────\n";
    $mail->AltBody .= "Nombre: $nombre\n";
    $mail->AltBody .= "Email: $email_remitente\n";
    $mail->AltBody .= "Asunto: $asunto\n\n";
    $mail->AltBody .= "MENSAJE\n";
    $mail->AltBody .= "───────\n";
    $mail->AltBody .= "$mensaje_usuario\n\n";
    $mail->AltBody .= "DISPOSITIVO Y NAVEGADOR\n";
    $mail->AltBody .= "──────────────────────\n";
    $mail->AltBody .= "SO: $os\n";
    $mail->AltBody .= "Navegador: $browser $browser_version\n";
    $mail->AltBody .= "Idioma: $idioma_principal\n\n";
    $mail->AltBody .= "RED Y UBICACIÓN\n";
    $mail->AltBody .= "───────────────\n";
    $mail->AltBody .= "IP: $ip_usuario\n";
    $mail->AltBody .= "Protocolo: $protocolo\n\n";
    $mail->AltBody .= "NAVEGACIÓN\n";
    $mail->AltBody .= "──────────\n";
    $mail->AltBody .= "Origen: $pagina_origen\n";
    $mail->AltBody .= "Página actual: $pagina_actual\n\n";
    $mail->AltBody .= "SERVIDOR\n";
    $mail->AltBody .= "────────\n";
    $mail->AltBody .= "Host: $host_servidor\n";
    $mail->AltBody .= "Puerto: $puerto\n";
    $mail->AltBody .= "Software: $software_servidor\n";
    $mail->AltBody .= "Fecha: $fecha_servidor\n";
    $mail->AltBody .= "Zona horaria: $zona_horaria_servidor\n\n";
    $mail->AltBody .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $mail->AltBody .= "© " . date('Y') . " Cyber Crime System";
    
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado con éxito']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo]);
}
?>
