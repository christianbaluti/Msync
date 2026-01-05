<?php
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;
    private $db;
    private $log_path;

    public function __construct() {
        $project_root = dirname(__DIR__, 2);
        $this->log_path = $project_root . '/public/error.log';

        try {
            $this->db = (new Database())->connect();
            $settings = $this->getSmtpSettings();

            $this->mailer = new PHPMailer(true);
            $this->mailer->SMTPDebug = 2; // turn to 2 when debugging
            $this->mailer->Debugoutput = 'error_log';
            $this->mailer->isSMTP();
            $this->mailer->SMTPAuth = true;

            // default from DB
            $this->mailer->Host       = $settings['smtp_host'] ?? 'mail.hallmark.mw';
            $this->mailer->Username   = $settings['smtp_username'] ?? 'noreply@hallmark.mw';
            $this->mailer->Password   = $settings['smtp_password'] ?? '';
            $this->mailer->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port       = (int)($settings['smtp_port'] ?? 465);

            $from_email = $settings['smtp_from_email'] ?? 'noreply@hallmark.mw';
            $from_name  = $settings['smtp_from_name'] ?? 'MyIMM App';
            $this->mailer->setFrom($from_email, $from_name);

        } catch (Exception $e) {
            $msg = "[" . date("Y-m-d H:i:s") . "] Mailer init error: " . $e->getMessage() . "\n";
            error_log($msg, 3, $this->log_path);
            $this->mailer = null;
        }
    }

    private function getSmtpSettings() {
        try {
            $stmt = $this->db->prepare(
                "SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("[".date("Y-m-d H:i:s")."] getSmtpSettings failed: ".$e->getMessage()."\n", 3, $this->log_path);
            return [];
        }
    }

    public function send($to_email, $to_name, $subject, $body, $attachments = []) {
        if ($this->mailer === null) {
            error_log("[".date("Y-m-d H:i:s")."] Mailer send() called but mailer not initialized.\n", 3, $this->log_path);
            return false;
        }

        try {
            // clear old data
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags($body);

            foreach ($attachments as $a) {
                $this->mailer->addStringAttachment($a['content'], $a['filename']);
            }

            try {
                // Attempt #1 — SSL 465
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = 587;
                $this->mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $this->mailer->send();
                return true;

            } catch (Exception $e1) {
                // Log first failure
                error_log("[".date("Y-m-d H:i:s")."] SMTP SSL(465) failed: ".$e1->getMessage()."\n", 3, $this->log_path);

                // Attempt #2 — TLS 587
                $this->mailer->smtpClose(); // reset connection
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = 587;

                try {
                    $this->mailer->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $this->mailer->send();
                    return true;
                } catch (Exception $e2) {
                    error_log("[".date("Y-m-d H:i:s")."] SMTP TLS(587) also failed: ".$e2->getMessage().
                        " | PHPMailer info: ".$this->mailer->ErrorInfo."\n", 3, $this->log_path);
                    return false;
                }
            }

        } catch (Exception $ex) {
            error_log("[".date("Y-m-d H:i:s")."] Mailer Send Error: ".$ex->getMessage().
                " | SMTP Error: ".$this->mailer->ErrorInfo."\n", 3, $this->log_path);
            return false;
        }
    }
}
