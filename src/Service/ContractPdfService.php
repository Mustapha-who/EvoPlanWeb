<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Twig\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class ContractPdfService
{
    private $twig;
    private $projectDir;
    private $kernel;
    private $filesystem;

    public function __construct(
        Environment $twig,
        KernelInterface $kernel,
        Filesystem $filesystem
    ) {
        $this->twig = $twig;
        $this->kernel = $kernel;
        $this->projectDir = $kernel->getProjectDir();
        $this->filesystem = $filesystem;
    }

    /**
     * Generate a contract PDF
     *
     * @param string $partnerEmail The email address of the partner
     * @param string $partnerType The type of partner (sponsor, speaker, etc.)
     * @param string $eventName The name of the event
     * @param \DateTimeInterface $startDate The start date of the contract
     * @param \DateTimeInterface $endDate The end date of the contract
     * @param string $terms The terms of the contract
     * @return string The path to the generated PDF file
     * @throws \Exception
     */
    public function generateContractPdf(
        string $partnerEmail,
        string $partnerType,
        string $eventName,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $terms
    ): string {
        try {
            // Create directory for contracts if it doesn't exist
            $contractsDir = $this->projectDir . '/public/contracts';
            if (!$this->filesystem->exists($contractsDir)) {
                $this->filesystem->mkdir($contractsDir);
            }

            // Generate a unique filename for the contract
            $filename = 'contract_' . strtolower(str_replace(' ', '_', $eventName)) . '_' . 
                      strtolower(str_replace(['@', '.'], ['_', '_'], $partnerEmail)) . '_' . 
                      $startDate->format('Y-m-d') . '.pdf';
            $pdfPath = $contractsDir . '/' . $filename;
            
            // Generate HTML content
            $htmlContent = $this->twig->render('contract/pdf_template.html.twig', [
                'partnerEmail' => $partnerEmail,
                'partnerType' => $partnerType,
                'eventName' => $eventName,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'terms' => $terms,
            ]);
            
            // Configure Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', true);
            
            // Initialize Dompdf
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlContent);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render the PDF
            $dompdf->render();
            
            // Save to file
            file_put_contents($pdfPath, $dompdf->output());
            
            return $pdfPath;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error generating PDF: ' . $e->getMessage());
            
            // Create an HTML version as fallback
            $htmlFilename = str_replace('.pdf', '.html', $filename);
            $htmlPath = $contractsDir . '/' . $htmlFilename;
            
            file_put_contents($htmlPath, $htmlContent ?? $this->generateErrorHtml($partnerEmail, $eventName, $e->getMessage()));
            
            return $htmlPath;
        }
    }
    
    /**
     * Generate an error HTML page
     */
    private function generateErrorHtml(string $partnerEmail, string $eventName, string $errorMessage): string
    {
        return "
            <html>
                <head>
                    <title>Contract Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px; }
                        .error-container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                        .error-title { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
                        .error-details { background-color: #f8f9fa; padding: 15px; border-left: 3px solid #dc3545; margin-bottom: 20px; }
                        .button { background-color: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
                    </style>
                </head>
                <body>
                    <div class='error-container'>
                        <h1 class='error-title'>Error Generating Contract PDF</h1>
                        <p>There was an error generating the contract PDF for:</p>
                        <ul>
                            <li><strong>Partner:</strong> {$partnerEmail}</li>
                            <li><strong>Event:</strong> {$eventName}</li>
                        </ul>
                        <div class='error-details'>
                            <p><strong>Error Message:</strong> {$errorMessage}</p>
                        </div>
                        <p>Please contact the system administrator for assistance.</p>
                        <button onclick='window.history.back()' class='button'>Go Back</button>
                    </div>
                </body>
            </html>
        ";
    }
} 