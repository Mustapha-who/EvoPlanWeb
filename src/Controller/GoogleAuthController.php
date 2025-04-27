<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

class GoogleAuthController extends AbstractController
{
    private GoogleCalendarService $googleCalendarService;
    private string $projectDir;
    private Filesystem $filesystem;

    public function __construct(
        GoogleCalendarService $googleCalendarService, 
        string $projectDir,
        Filesystem $filesystem
    ) {
        $this->googleCalendarService = $googleCalendarService;
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
    }

    #[Route('/admin/google/auth', name: 'app_google_auth')]
    public function auth(): Response
    {
        // Redirect to Google's OAuth page
        $authUrl = $this->googleCalendarService->getAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/calendar-callback', name: 'app_google_callback')]
    public function callback(Request $request): Response
    {
        // Handle callback from Google
        $code = $request->query->get('code');
        
        if (!$code) {
            $this->addFlash('error', 'Authentication failed: No authorization code received');
            return $this->redirectToRoute('login');
        }
        
        try {
            // Exchange code for access token
            $accessToken = $this->googleCalendarService->fetchAccessTokenWithAuthCode($code);
            
            // Store the token in a file
            $tokenPath = $this->projectDir . '/var/google_token.json';
            $this->filesystem->dumpFile($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT));
            
            $this->addFlash('success', 'Successfully authenticated with Google Calendar and saved token to ' . $tokenPath);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Authentication failed: ' . $e->getMessage());
        }
        
        // Redirect back to login page since we know it exists
        return $this->redirectToRoute('login');
    }
} 