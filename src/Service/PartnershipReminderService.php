<?php

namespace App\Service;

use App\Entity\Partnership;
use App\Repository\PartnershipRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class PartnershipReminderService
{
    private GoogleCalendarService $googleCalendarService;
    private PartnershipRepository $partnershipRepository;
    private string $projectDir;
    private Filesystem $filesystem;

    public function __construct(
        GoogleCalendarService $googleCalendarService,
        PartnershipRepository $partnershipRepository,
        string $projectDir,
        Filesystem $filesystem
    ) {
        $this->googleCalendarService = $googleCalendarService;
        $this->partnershipRepository = $partnershipRepository;
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
    }

    /**
     * Check ending partnerships and create reminders
     * 
     * @return array Statistics about created reminders
     */
    public function checkEndingPartnerships(): array
    {
        $stats = [
            'checked' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'not_authenticated' => false
        ];
        
        // Load access token if exists
        $tokenPath = $this->projectDir . '/var/google_token.json';
        $isAuthenticated = false;
        
        if (file_exists($tokenPath)) {
            try {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $this->googleCalendarService->setAccessToken($accessToken);
                $isAuthenticated = true;
            } catch (\Exception $e) {
                $stats['not_authenticated'] = true;
                return $stats;
            }
        } else {
            $stats['not_authenticated'] = true;
            return $stats;
        }
        
        // Calculate the date range (today + 7 days)
        $today = new \DateTime();
        $sevenDaysFromNow = (new \DateTime())->modify('+7 days');
        
        // Find partnerships ending in the next 7 days
        $endingPartnerships = $this->partnershipRepository->findPartnershipsEndingBetween($today, $sevenDaysFromNow);
        $stats['checked'] = count($endingPartnerships);
        
        if (empty($endingPartnerships)) {
            return $stats;
        }
        
        // Check and create events for each partnership
        foreach ($endingPartnerships as $partnership) {
            try {
                // Skip if event already exists
                if ($this->googleCalendarService->partnershipEndReminderExists($partnership)) {
                    $stats['skipped']++;
                    continue;
                }
                
                $eventId = $this->googleCalendarService->createPartnershipEndReminder($partnership);
                if ($eventId) {
                    $stats['created']++;
                } else {
                    $stats['errors']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if a specific partnership has a reminder and create one if needed
     * 
     * @param Partnership $partnership
     * @return array Result containing 'success', 'message', and optional 'event_id'
     */
    public function checkAndCreateReminderForPartnership(Partnership $partnership): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'event_id' => null
        ];
        
        // Check if end date is within 7 days
        $today = new \DateTime();
        $endDate = $partnership->getDateFin();
        
        if (!$endDate) {
            $result['message'] = 'Partnership does not have an end date';
            return $result;
        }
        
        $daysDiff = $endDate->diff($today)->days;
        $endDateInFuture = $today < $endDate;
        
        // Only create reminder if end date is within the next 7 days
        if (!$endDateInFuture || $daysDiff > 7) {
            $result['message'] = 'Partnership not ending within the next 7 days';
            return $result;
        }
        
        // Load access token if exists
        $tokenPath = $this->projectDir . '/var/google_token.json';
        
        if (!file_exists($tokenPath)) {
            $result['message'] = 'Google Calendar authentication is required';
            return $result;
        }
        
        try {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->googleCalendarService->setAccessToken($accessToken);
            
            // Check if reminder already exists
            if ($this->googleCalendarService->partnershipEndReminderExists($partnership)) {
                $result['success'] = true;
                $result['message'] = 'Reminder already exists for this partnership';
                return $result;
            }
            
            // Create the reminder
            $eventId = $this->googleCalendarService->createPartnershipEndReminder($partnership);
            
            if ($eventId) {
                $result['success'] = true;
                $result['message'] = 'End reminder created successfully';
                $result['event_id'] = $eventId;
            } else {
                $result['message'] = 'Failed to create end reminder';
            }
        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Check a single partnership and create reminders if needed
     * This method will be called automatically when partnerships are loaded
     * 
     * @param Partnership $partnership
     * @return bool True if reminder was created, false otherwise
     */
    public function checkSinglePartnership(Partnership $partnership): bool
    {
        error_log("[DEBUG] Starting checkSinglePartnership for partnership #" . $partnership->getIdPartnership());
        
        // Skip if no end date
        if (!$partnership->getDateFin()) {
            error_log("[DEBUG] Partnership has no end date, skipping");
            return false;
        }
        
        $endDate = $partnership->getDateFin();
        $today = new \DateTime();
        
        // Debug output
        error_log(sprintf(
            "[DEBUG] Checking partnership #%d, ends: %s, today: %s", 
            $partnership->getIdPartnership(),
            $endDate->format('Y-m-d'),
            $today->format('Y-m-d')
        ));
        
        // Check if partnership ends within 7 days
        if ($today < $endDate) {
            $diff = $today->diff($endDate);
            
            error_log(sprintf("[DEBUG] Days until end: %d", $diff->days));
            
            if ($diff->days <= 7) {
                error_log("[DEBUG] Partnership ending within 7 days, checking for reminders");
                
                // CRITICAL FIX: Load access token like we do in the command
                $tokenPath = $this->projectDir . '/var/google_token.json';
                error_log("[DEBUG] Looking for token at: " . $tokenPath);
                
                if (!file_exists($tokenPath)) {
                    error_log("[ERROR] No access token file found");
                    return false;
                }
                
                try {
                    error_log("[DEBUG] Loading Google access token");
                    $accessToken = json_decode(file_get_contents($tokenPath), true);
                    
                    // Check if token is valid
                    if (!isset($accessToken['access_token'])) {
                        error_log("[ERROR] Invalid token format - missing access_token");
                        return false;
                    }
                    
                    error_log("[DEBUG] Token loaded successfully. Setting access token.");
                    $this->googleCalendarService->setAccessToken($accessToken);
                    
                    // Check if calendar reminder already exists
                    error_log("[DEBUG] Checking if reminder already exists");
                    if (!$this->googleCalendarService->partnershipEndReminderExists($partnership)) {
                        error_log("[DEBUG] Creating reminder automatically");
                        
                        // Create the reminder
                        $eventId = $this->googleCalendarService->createPartnershipEndReminder($partnership);
                        
                        if ($eventId) {
                            error_log(sprintf("[SUCCESS] Reminder created automatically with ID: %s", $eventId));
                            return true;
                        } else {
                            error_log("[ERROR] Failed to create event - no event ID returned");
                        }
                    } else {
                        error_log("[DEBUG] Reminder already exists, skipping");
                    }
                } catch (\Exception $e) {
                    error_log(sprintf("[ERROR] Exception while creating reminder: %s", $e->getMessage()));
                    error_log(sprintf("[ERROR] Exception trace: %s", $e->getTraceAsString()));
                }
            }
        }
        
        return false;
    }
} 