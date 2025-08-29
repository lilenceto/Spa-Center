<?php
/**
 * Working Email Helper Class for Spa Center using PHPMailer
 * This version includes PHPMailer files and will work with Gmail SMTP
 */

// Include PHPMailer files
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelperWorking {
    private $from_email;
    private $from_name;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $use_smtp;
    
    public function __construct() {
        // Gmail Configuration - UPDATE THESE VALUES!
        $this->from_email = 'lilid0911@gmail.com';
        $this->from_name = 'Spa Center';
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_username = 'lilid0911@gmail.com';
        $this->smtp_password = 'qiketnqcusvqcprr'; // ✅ Gmail app password configured
        $this->use_smtp = true;
    }
    
    /**
     * Send reservation confirmation email
     */
    public function sendReservationConfirmation($user_email, $user_name, $reservation_data) {
        $subject = 'Потвърждение на резервация - Spa Center';
        
        $html_content = $this->getReservationConfirmationTemplate($user_name, $reservation_data);
        $text_content = $this->getReservationConfirmationText($user_name, $reservation_data);
        
        return $this->sendEmail($user_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send reservation status update email
     */
    public function sendStatusUpdate($user_email, $user_name, $reservation_data, $old_status, $new_status) {
        $subject = 'Промяна в статуса на резервация - Spa Center';
        
        $html_content = $this->getStatusUpdateTemplate($user_name, $reservation_data, $old_status, $new_status);
        $text_content = $this->getStatusUpdateText($user_name, $reservation_data, $old_status, $new_status);
        
        return $this->sendEmail($user_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send reminder email before appointment
     */
    public function sendAppointmentReminder($user_email, $user_name, $reservation_data) {
        $subject = 'Напомняне за резервация - Spa Center';
        
        $html_content = $this->getReminderTemplate($user_name, $reservation_data);
        $text_content = $this->getReminderText($user_name, $reservation_data);
        
        return $this->sendEmail($user_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send cancellation confirmation email
     */
    public function sendCancellationConfirmation($user_email, $user_name, $reservation_data) {
        $subject = 'Потвърждение за отмяна на резервация - Spa Center';
        
        $html_content = $this->getCancellationTemplate($user_name, $reservation_data);
        $text_content = $this->getCancellationText($user_name, $reservation_data);
        
        return $this->sendEmail($user_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Main email sending function using PHPMailer
     */
    private function sendEmail($to_email, $subject, $html_content, $text_content = '') {
        try {
            // Create new PHPMailer instance
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Enable debug output (remove in production)
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to_email);
            $mail->addReplyTo($this->from_email, $this->from_name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_content;
            $mail->AltBody = $text_content;
            
            // Send email
            $mail->send();
            
            // Log successful email
            $this->logEmail($to_email, $subject, 'success');
            return true;
            
        } catch (Exception $e) {
            // Log failed email with error details
            $this->logEmail($to_email, $subject, 'failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log email attempts for debugging
     */
    private function logEmail($to_email, $subject, $status) {
        $log_entry = date('Y-m-d H:i:s') . " | To: $to_email | Subject: $subject | Status: $status\n";
        file_put_contents('email_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Test Gmail SMTP connection
     */
    public function testConnection() {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Test connection without sending
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return true;
        } catch (Exception $e) {
            return "Connection failed: " . $e->getMessage();
        }
    }
    
    // Template methods (same as before)
    private function getReservationConfirmationTemplate($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $duration = $reservation_data['duration'];
        $employee_name = $reservation_data['employee_name'] ?? 'Автоматично присвоен';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Потвърждение на резервация</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4a90e2; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #4a90e2; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 10px 20px; background: #4a90e2; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Резервацията е потвърдена!</h1>
                </div>
                <div class='content'>
                    <p>Здравейте, <strong>$user_name</strong>!</p>
                    <p>Вашата резервация е успешно създадена и очаква потвърждение от нашия екип.</p>
                    
                    <div class='details'>
                        <h3>Детайли на резервацията:</h3>
                        <p><strong>Услуга:</strong> $service_name</p>
                        <p><strong>Дата:</strong> $date</p>
                        <p><strong>Час:</strong> $time</p>
                        <p><strong>Продължителност:</strong> $duration минути</p>
                        <p><strong>Специалист:</strong> $employee_name</p>
                    </div>
                    
                    <p>Ще получите потвърждение за резервацията в най-кратки срокове.</p>
                    
                    <p style='text-align: center;'>
                        <a href='http://localhost/Spa-Center/reservations.php' class='button'>Преглед на резервациите</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>Този имейл е изпратен автоматично. Моля, не отговаряйте на него.</p>
                    <p>Spa Center - Вашият партньор за релаксация и красота</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getReservationConfirmationText($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $duration = $reservation_data['duration'];
        $employee_name = $reservation_data['employee_name'] ?? 'Автоматично присвоен';
        
        return "
        Потвърждение на резервация - Spa Center
        
        Здравейте, $user_name!
        
        Вашата резервация е успешно създадена и очаква потвърждение от нашия екип.
        
        Детайли на резервацията:
        - Услуга: $service_name
        - Дата: $date
        - Час: $time
        - Продължителност: $duration минути
        - Специалист: $employee_name
        
        Ще получите потвърждение за резервацията в най-кратки срокове.
        
        Spa Center - Вашият партньор за релаксация и красота";
    }
    
    private function getStatusUpdateTemplate($user_name, $reservation_data, $old_status, $new_status) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $status_text = $this->getStatusText($new_status);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Промяна в статуса на резервация</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #28a745; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Статусът на резервацията е променен</h1>
                </div>
                <div class='content'>
                    <p>Здравейте, <strong>$user_name</strong>!</p>
                    <p>Статусът на вашата резервация е променен от <strong>$old_status</strong> на <strong>$status_text</strong>.</p>
                    
                    <div class='details'>
                        <h3>Детайли на резервацията:</h3>
                        <p><strong>Услуга:</strong> $service_name</p>
                        <p><strong>Дата:</strong> $date</p>
                        <p><strong>Час:</strong> $time</p>
                        <p><strong>Нов статус:</strong> $status_text</p>
                    </div>
                    
                    <p>Моля, проверете вашите резервации за повече информация.</p>
                </div>
                <div class='footer'>
                    <p>Този имейл е изпратен автоматично. Моля, не отговаряйте на него.</p>
                    <p>Spa Center - Вашият партньор за релаксация и красота</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getStatusUpdateText($user_name, $reservation_data, $old_status, $new_status) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $status_text = $this->getStatusText($new_status);
        
        return "
        Промяна в статуса на резервация - Spa Center
        
        Здравейте, $user_name!
        
        Статусът на вашата резервация е променен от $old_status на $status_text.
        
        Детайли на резервацията:
        - Услуга: $service_name
        - Дата: $date
        - Час: $time
        - Нов статус: $status_text
        
        Моля, проверете вашите резервации за повече информация.
        
        Spa Center - Вашият партньор за релаксация и красота";
    }
    
    private function getReminderTemplate($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $duration = $reservation_data['duration'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Напомняне за резервация</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ Напомняне за резервация</h1>
                </div>
                <div class='content'>
                    <p>Здравейте, <strong>$user_name</strong>!</p>
                    <p>Това е напомняне за вашата резервация утре.</p>
                    
                    <div class='details'>
                        <h3>Детайли на резервацията:</h3>
                        <p><strong>Услуга:</strong> $service_name</p>
                        <p><strong>Дата:</strong> $date</p>
                        <p><strong>Час:</strong> $time</p>
                        <p><strong>Продължителност:</strong> $duration минути</p>
                    </div>
                    
                    <p>Моля, пристигнете 10 минути преди започването на процедурата.</p>
                </div>
                <div class='footer'>
                    <p>Този имейл е изпратен автоматично. Моля, не отговаряйте на него.</p>
                    <p>Spa Center - Вашият партньор за релаксация и красота</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getReminderText($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        $duration = $reservation_data['duration'];
        
        return "
        Напомняне за резервация - Spa Center
        
        Здравейте, $user_name!
        
        Това е напомняне за вашата резервация утре.
        
        Детайли на резервацията:
        - Услуга: $service_name
        - Дата: $date
        - Час: $time
        - Продължителност: $duration минути
        
        Моля, пристигнете 10 минути преди започването на процедурата.
        
        Spa Center - Вашият партньор за релаксация и красота";
    }
    
    private function getCancellationTemplate($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Потвърждение за отмяна на резервация</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #dc3545; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Резервацията е отменена</h1>
                </div>
                <div class='content'>
                    <p>Здравейте, <strong>$user_name</strong>!</p>
                    <p>Вашата резервация е успешно отменена.</p>
                    
                    <div class='details'>
                        <h3>Детайли на отменената резервация:</h3>
                        <p><strong>Услуга:</strong> $service_name</p>
                        <p><strong>Дата:</strong> $date</p>
                        <p><strong>Час:</strong> $time</p>
                    </div>
                    
                    <p>Надяваме се да се видим скоро за нова резервация!</p>
                </div>
                <div class='footer'>
                    <p>Този имейл е изпратен автоматично. Моля, не отговаряйте на него.</p>
                    <p>Spa Center - Вашият партньор за релаксация и красота</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getCancellationText($user_name, $reservation_data) {
        $service_name = $reservation_data['service_name'];
        $date = $reservation_data['date'];
        $time = $reservation_data['time'];
        
        return "
        Потвърждение за отмяна на резервация - Spa Center
        
        Здравейте, $user_name!
        
        Вашата резервация е успешно отменена.
        
        Детайли на отменената резервация:
        - Услуга: $service_name
        - Дата: $date
        - Час: $time
        
        Надяваме се да се видим скоро за нова резервация!
        
        Spa Center - Вашият партньор за релаксация и красота";
    }
    
    private function getStatusText($status) {
        $status_map = [
            'Awaiting' => 'Очаква потвърждение',
            'Approved' => 'Одобрена',
            'Completed' => 'Завършена',
            'Cancelled' => 'Отменена'
        ];
        
        return $status_map[$status] ?? $status;
    }
}
?>
