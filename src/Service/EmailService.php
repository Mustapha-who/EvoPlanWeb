<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private ParameterBagInterface $params;
    private ContractPdfService $contractPdfService;
    private ?LoggerInterface $logger;
    private string $fromEmail;

    public function __construct(
        MailerInterface $mailer, 
        ParameterBagInterface $params,
        ContractPdfService $contractPdfService,
        LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer;
        $this->params = $params;
        $this->contractPdfService = $contractPdfService;
        $this->logger = $logger;
        
        // Get from email from env variable or use a default domain-based email
        // Using domain-based email helps with deliverability
        $this->fromEmail = 'yacineamrouche2512@gmail.com';
    }

    /**
     * Get the configured FROM email address from .env or use a default
     */
    private function getConfiguredFromEmail(): string
    {
        // Try to extract email from MAILER_DSN if it's Gmail
        $mailerDsn = $_ENV['MAILER_DSN'] ?? '';
        if (strpos($mailerDsn, 'gmail://') === 0) {
            preg_match('/gmail:\/\/([^:]+):/i', $mailerDsn, $matches);
            if (!empty($matches[1])) {
                return $matches[1];
            }
        }
        
        // Default email - using a domain-based address improves deliverability
        return 'noreply@evoplan.com';
    }

    /**
     * Send a contract email to a partner with the contract attached
     *
     * @param string $partnerEmail The email address of the partner
     * @param string $partnerType The type of partner (sponsor, speaker, etc.)
     * @param string $eventName The name of the event
     * @param \DateTimeInterface $startDate The start date of the contract
     * @param \DateTimeInterface $endDate The end date of the contract
     * @param string $terms The terms of the contract
     * @return bool Whether the email was sent successfully
     */
    public function sendContractEmail(
        string $partnerEmail,
        string $partnerType,
        string $eventName,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $terms
    ): bool {
        // Validate the partner email to avoid errors
        if (!filter_var($partnerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->log('error', "Invalid partner email address: {$partnerEmail}");
            return false;
        }
        
        try {
            // Log before contract generation
            $this->log('info', "Starting contract generation for partner: {$partnerEmail}, event: {$eventName}");
            
            // Generate the contract document (PDF or HTML as fallback)
            $filePath = $this->contractPdfService->generateContractPdf(
                $partnerEmail,
                $partnerType,
                $eventName,
                $startDate,
                $endDate,
                $terms
            );
            
            $this->log('info', "Contract generated: {$filePath}");

            // Verify the file exists
            if (!file_exists($filePath)) {
                $this->log('error', "Generated file does not exist: {$filePath}");
                return false;
            }

            // Determine MIME type based on file extension
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = ($fileExtension === 'pdf') ? 'application/pdf' : 'text/html';
            $fileName = ($fileExtension === 'pdf') ? 'contract.pdf' : 'contract.html';
            
            $this->log('info', "Preparing email with attachment: {$fileName} ({$mimeType})");

            // Create the email with the document attachment
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($partnerEmail)
                ->subject("Contract for partnership with $eventName")
                ->html($this->generateContractEmailContent(
                    $partnerType,
                    $eventName,
                    $startDate,
                    $endDate,
                    $terms,
                    $fileExtension === 'pdf'
                ));
                
            // Add the attachment
            try {
                $fileToAttach = new File($filePath);
                $email->addPart(new DataPart($fileToAttach, $fileName, $mimeType));
                $this->log('info', "Attachment added to email");
            } catch (\Exception $e) {
                $this->log('error', "Failed to attach file: " . $e->getMessage());
                // Continue sending the email without attachment
            }
            
            // Send the email
            $this->log('info', "Sending email to: {$partnerEmail} from: {$this->fromEmail}");
            $this->mailer->send($email);
            
            $this->log('info', "Email sent successfully");
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->log('error', "Transport error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->log('error', "Email sending error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate the HTML content for the contract email
     */
    private function generateContractEmailContent(
        string $partnerType,
        string $eventName,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $terms,
        bool $isPdf = true
    ): string {
        $fileType = $isPdf ? 'PDF' : 'HTML';
        
        return "
            <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4e73df; color: white; padding: 10px 20px; text-align: center; }
                        .content { padding: 20px; border: 1px solid #ddd; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                        .section { margin-bottom: 15px; }
                        .label { font-weight: bold; }
                        .button { background-color: #4e73df; color: white; display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                        .button:hover { background-color: #2e59d9; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Partnership Contract</h2>
                        </div>
                        <div class='content'>
                            <div class='section'>
                                <p>Dear " . ucfirst($partnerType) . ",</p>
                                <p>We are pleased to confirm your partnership with <strong>" . $eventName . "</strong>.</p>
                                <p>Please find attached your contract for this partnership in <strong>{$fileType}</strong> format.</p>
                            </div>
                            
                            <div class='section'>
                                <p class='label'>Partnership Details:</p>
                                <ul>
                                    <li><strong>Event:</strong> " . $eventName . "</li>
                                    <li><strong>Start Date:</strong> " . $startDate->format('Y-m-d') . "</li>
                                    <li><strong>End Date:</strong> " . $endDate->format('Y-m-d') . "</li>
                                </ul>
                            </div>
                            
                            <div class='section'>
                                <p class='label'>Next Steps:</p>
                                <p>The attached contract includes all the terms and conditions of our partnership." . ($isPdf ? "" : " You can save the HTML version as a PDF using your browser's print function.") . "</p>
                                <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
                            </div>
                            
                            <div class='section'>
                                <p>Thank you for your partnership!</p>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email from EvoPlan. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
            </html>
        ";
    }
    
    /**
     * Log a message if the logger is available, otherwise use error_log
     */
    private function log(string $level, string $message): void
    {
        if ($this->logger) {
            switch ($level) {
                case 'error':
                    $this->logger->error($message);
                    break;
                case 'warning':
                    $this->logger->warning($message);
                    break;
                default:
                    $this->logger->info($message);
            }
        } else {
            error_log("[{$level}] {$message}");
        }
    }
} 