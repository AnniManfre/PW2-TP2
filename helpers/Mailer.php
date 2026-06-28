<?php


require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

       
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com'; 
        $this->mail->SMTPAuth   = true;
        
       
        $this->mail->Username   = 'triviaclashpw2@gmail.com'; 
        $this->mail->Password   = 'sdypnfvcakzfikda'; 
        
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->CharSet    = 'UTF-8';


        $this->mail->setFrom('triviaclashpw2@gmail.com', 'Trivia Clash');
    }


    public function enviarTokenActivacion($correoDestino, $nombreUsuario, $token)
    {
        try {
            $this->mail->addAddress($correoDestino, $nombreUsuario);

          
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Confirma tu cuenta - Código de Activación';
            
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <h2 style='color: #333333; border-bottom: 2px solid #4285F4; padding-bottom: 10px;'>¡Hola, $nombreUsuario!</h2>
                    <p style='color: #666666; font-size: 16px;'>Gracias por registrarte en <strong>Trivia Clash</strong>. Para empezar a jugar y competir con tus amigos, necesitamos verificar tu cuenta.</p>
                    <div style='background-color: #f4f4f4; padding: 15px; border-radius: 6px; text-align: center; margin: 20px 0;'>
                        <p style='margin: 0; color: #333333; font-size: 14px;'>Tu código de verificación es:</p>
                        <h1 style='margin: 5px 0; color: #4285F4; font-size: 32px; letter-spacing: 4px;'>$token</h1>
                    </div>
                    <p style='color: #666666; font-size: 14px;'>Ingresá este código en la pantalla de validación de la aplicación.</p>
                    <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
                    <p style='color: #999999; font-size: 12px; text-align: center;'>Este es un correo automático, por favor no lo respondas.</p>
                </div>
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
           
            error_log("PHPMailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}