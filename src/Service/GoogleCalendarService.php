<?php

namespace App\Service;

use App\Entity\Partnership;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventReminders;
use Google_Service_Calendar_EventReminder;
use Google_Service_Calendar_EventAttendee;

class GoogleCalendarService
{
    private $calendarService;
    private $client;
    private $projectDir;

    public function __construct(string $credentialsPath, string $projectDir = null)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('EvoPlanWeb Partnership Reminder');
        $this->client->setScopes([Google_Service_Calendar::CALENDAR]);
        $this->projectDir = $projectDir;
        
        // Load client credentials from the JSON file
        $clientCredentials = json_decode(file_get_contents($credentialsPath), true);
        
        // Configure the client for OAuth Web auth
        $this->client->setClientId($clientCredentials['web']['client_id']);
        $this->client->setClientSecret($clientCredentials['web']['client_secret']);
        $this->client->setRedirectUri('http://localhost:8000/calendar-callback');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        // Initialize the service
        $this->calendarService = new Google_Service_Calendar($this->client);
    }

    /**
     * Set the access token for API requests
     *
     * @param array $accessToken The access token
     * @return void
     */
    public function setAccessToken(array $accessToken): void
    {
        error_log("[DEBUG] GoogleCalendarService::setAccessToken called with: " . json_encode($accessToken, JSON_PRETTY_PRINT));
        
        // Validate access token format
        if (!isset($accessToken['access_token'])) {
            error_log("[ERROR] Invalid token format in GoogleCalendarService::setAccessToken - missing 'access_token'");
            throw new \InvalidArgumentException("Invalid access token format");
        }
        
        // Load client if not already loaded
        if (!$this->client) {
            error_log("[DEBUG] Initializing Google client in setAccessToken");
            $this->client = $this->getClient();
        }
        
        try {
            // Set the access token
            $this->client->setAccessToken($accessToken);
            error_log("[DEBUG] Access token set successfully in Google client");
            
            // Check if token needs refresh
            if ($this->client->isAccessTokenExpired()) {
                error_log("[DEBUG] Access token is expired, attempting to refresh");
                $this->refreshToken();
            }
        } catch (\Exception $e) {
            error_log("[ERROR] Exception in GoogleCalendarService::setAccessToken: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if the current token is valid
     */
    public function hasValidToken(): bool
    {
        try {
            // Get a minimal amount of data to test
            $this->calendarService->calendarList->listCalendarList(['maxResults' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get authorization URL for user to grant calendar access
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Exchange auth code for access token
     */
    public function fetchAccessTokenWithAuthCode(string $authCode): array
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        
        // Check for errors
        if (isset($accessToken['error'])) {
            throw new \Exception('Error obtaining access token: ' . 
                $accessToken['error'] . ' - ' . 
                ($accessToken['error_description'] ?? 'No description'));
        }
        
        return $accessToken;
    }

    /**
     * Check if a partnership end reminder already exists in Google Calendar
     *
     * @param Partnership $partnership The partnership to check
     * @param string $calendarId The calendar ID (default: primary)
     * @return bool True if a reminder exists, false otherwise
     */
    public function partnershipEndReminderExists(Partnership $partnership, string $calendarId = 'primary'): bool
    {
        if (!$partnership->getIdPartner() || !$partnership->getIdEvent()) {
            error_log("[WARN] Cannot check if reminder exists: partnership #" . $partnership->getIdPartnership() . " is missing partner or event");
            return false;
        }
        
        error_log("[DEBUG] Checking if reminder exists for partnership #" . $partnership->getIdPartnership());
        
        // Ensure we have a valid token
        if (!$this->ensureValidToken()) {
            error_log("[ERROR] No valid token available for checking reminders");
            return false;
        }
        
        try {
            // Get end date
            $endDate = $partnership->getDateFin()->format('Y-m-d');
            $partnerEmail = $partnership->getIdPartner()->getEmail();
            $eventName = $partnership->getIdEvent()->getNom();
            
            // Make sure we have calendarService ready
            if (!$this->calendarService) {
                error_log("[DEBUG] Creating Google Calendar service in partnershipEndReminderExists");
                $this->calendarService = new Google_Service_Calendar($this->client);
            }
            
            // Query parameters to look for matching events
            $params = [
                'q' => 'Partnership End: ' . $eventName,
                'timeMin' => date('c', strtotime('-7 days')), // Include recent events
                'timeMax' => date('c', strtotime('+30 days')), // Include upcoming events
                'showDeleted' => false,
                'singleEvents' => true,
                'maxResults' => 10,
                'orderBy' => 'startTime'
            ];
            
            error_log("[DEBUG] Searching calendar with query: " . json_encode($params));
            
            $events = $this->calendarService->events->listEvents($calendarId, $params);
            $items = $events->getItems();
            
            error_log("[DEBUG] Found " . count($items) . " candidate events");
            
            foreach ($items as $event) {
                // Check if this event is for our partnership
                $description = $event->getDescription() ?? '';
                
                if (strpos($description, 'Partnership with ' . $partnerEmail) !== false &&
                    strpos($description, 'Event: ' . $eventName) !== false) {
                    
                    error_log("[DEBUG] Found matching event: " . $event->getSummary() . " (ID: " . $event->getId() . ")");
                    return true;
                }
            }
            
            error_log("[DEBUG] No matching reminder found");
            return false;
            
        } catch (\Exception $e) {
            error_log("[ERROR] Exception in partnershipEndReminderExists: " . $e->getMessage());
            // Return false but don't throw - we want to create a new reminder on error
            return false;
        }
    }

    /**
     * Create a calendar event reminder for a partnership ending soon
     *
     * @param Partnership $partnership The partnership to create a reminder for
     * @param string $calendarId The calendar ID (default: primary)
     * @return string|null The ID of the created event, or null on failure
     */
    public function createPartnershipEndReminder(Partnership $partnership, string $calendarId = 'primary'): ?string
    {
        error_log("[DEBUG] Creating partnership end reminder for partnership #" . $partnership->getIdPartnership());
        
        $partner = $partnership->getIdPartner();
        $event = $partnership->getIdEvent();
        $endDate = $partnership->getDateFin();
        
        if (!$partner || !$event || !$endDate) {
            error_log("[ERROR] Cannot create reminder: missing partner, event, or end date");
            return null;
        }
        
        // Ensure we have a valid token
        if (!$this->ensureValidToken()) {
            error_log("[ERROR] No valid token available for creating reminders");
            return null;
        }
        
        try {
            // Make sure we have calendarService ready
            if (!$this->calendarService) {
                error_log("[DEBUG] Creating Google Calendar service in createPartnershipEndReminder");
                $this->calendarService = new Google_Service_Calendar($this->client);
            }
            
            // Create a calendar event
            $calEvent = new Google_Service_Calendar_Event();
            
            // Set title
            $title = 'Partnership End: ' . $event->getNom();
            $calEvent->setSummary($title);
            
            // Set description
            $description = "Partnership with " . $partner->getEmail() . " for event \"" . $event->getNom() . "\" is ending.\n\n";
            $description .= "Event: " . $event->getNom() . "\n";
            $description .= "Partner: " . $partner->getEmail() . " (" . $partner->getEmail() . ")\n";
            $description .= "Partnership Type: " . $partner->getTypePartner() . "\n";
            $description .= "Start Date: " . $partnership->getDateDebut()->format('Y-m-d') . "\n";
            $description .= "End Date: " . $endDate->format('Y-m-d') . "\n";
            
            if ($partnership->getTerms()) {
                $description .= "\nTerms: " . $partnership->getTerms() . "\n";
            }
            
            $calEvent->setDescription($description);
            
            // Set event date (all-day event on the end date)
            $eventDate = new Google_Service_Calendar_EventDateTime();
            $eventDate->setDate($endDate->format('Y-m-d'));
            $calEvent->setStart($eventDate);
            
            // End date is same as start for all-day events
            $endEventDate = new Google_Service_Calendar_EventDateTime();
            $endEventDate->setDate($endDate->format('Y-m-d'));
            $calEvent->setEnd($endEventDate);
            
            // Add reminders
            $reminder = new Google_Service_Calendar_EventReminders();
            $reminder->setUseDefault(false);
            
            $overrides = [
                new Google_Service_Calendar_EventReminder([
                    'method' => 'email',
                    'minutes' => 24 * 60 // 1 day before
                ]),
                new Google_Service_Calendar_EventReminder([
                    'method' => 'popup',
                    'minutes' => 24 * 60 // 1 day before
                ])
            ];
            
            $reminder->setOverrides($overrides);
            $calEvent->setReminders($reminder);
            
            // IMPORTANT: Add the partner as an attendee to share the calendar event with them
            $attendee = new Google_Service_Calendar_EventAttendee();
            $attendee->setEmail($partner->getEmail());
            $attendee->setResponseStatus('needsAction');
            $calEvent->setAttendees([$attendee]);
            
            // Set event color (red)
            $calEvent->setColorId('11');
            
            // Set event visibility to public
            $calEvent->setVisibility('public');
            
            // Create the event
            error_log("[DEBUG] Creating Google Calendar event with title: " . $title);
            
            // Using optParams to set sendUpdates to 'all' to notify attendees
            $optParams = ['sendUpdates' => 'all'];
            $createdEvent = $this->calendarService->events->insert($calendarId, $calEvent, $optParams);
            
            if ($createdEvent && $createdEvent->getId()) {
                error_log("[SUCCESS] Created calendar event with ID: " . $createdEvent->getId() . " and sent invitation to partner");
                return $createdEvent->getId();
            } else {
                error_log("[ERROR] Failed to create event - no ID returned");
                return null;
            }
            
        } catch (\Exception $e) {
            error_log("[ERROR] Exception creating calendar event: " . $e->getMessage());
            error_log("[ERROR] Exception trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Refresh the access token using refresh token 
     *
     * @return bool True if successfully refreshed
     * @throws \Exception If token cannot be refreshed
     */
    private function refreshToken(): bool
    {
        error_log("[DEBUG] Attempting to refresh Google access token");
        
        // Check if we have a refresh token
        $refreshToken = $this->client->getRefreshToken();
        if (!$refreshToken) {
            error_log("[ERROR] No refresh token available for refresh");
            throw new \Exception('Access token has expired and no refresh token is available. Please re-authenticate.');
        }
        
        try {
            $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            
            // Check for errors
            if (isset($newAccessToken['error'])) {
                error_log("[ERROR] Error refreshing token: " . $newAccessToken['error']);
                throw new \Exception('Error refreshing access token: ' . 
                    $newAccessToken['error'] . ' - ' . 
                    ($newAccessToken['error_description'] ?? 'No description'));
            }
            
            error_log("[DEBUG] Token refreshed successfully");
            return true;
        } catch (\Exception $e) {
            error_log("[ERROR] Exception while refreshing token: " . $e->getMessage());
            throw new \Exception('Failed to refresh access token: ' . $e->getMessage() . 
                ' Please re-authenticate at /admin/google/auth');
        }
    }

    /**
     * Checks if client is initialized with a valid token
     * and attempts to load one from the token file if needed
     *
     * @param bool $tryToLoad Whether to try to load the token from file if not set
     * @return bool True if client has a valid token
     */
    public function ensureValidToken(bool $tryToLoad = true): bool
    {
        error_log("[DEBUG] Checking if Google client has valid token");
        
        // If client is not initialized, initialize it
        if (!$this->client) {
            error_log("[DEBUG] Client not initialized, initializing it");
            $this->client = new Google_Client();
            $this->client->setApplicationName('EvoPlanWeb Partnership Reminder');
            $this->client->setScopes([Google_Service_Calendar::CALENDAR]);
        }
        
        // Check if client already has a token
        if ($this->client->getAccessToken()) {
            error_log("[DEBUG] Client already has a token set");
            
            // Check if token is expired and can be refreshed
            if ($this->client->isAccessTokenExpired()) {
                error_log("[DEBUG] Token is expired, attempting to refresh");
                try {
                    $this->refreshToken();
                    return true;
                } catch (\Exception $e) {
                    error_log("[ERROR] Failed to refresh token: " . $e->getMessage());
                    return false;
                }
            }
            
            // Make sure the calendarService is initialized
            if (!$this->calendarService) {
                $this->calendarService = new Google_Service_Calendar($this->client);
            }
            
            return true;
        }
        
        // No token set, try to load from file if requested
        if ($tryToLoad) {
            error_log("[DEBUG] No token set, trying to load from file");
            $tokenPath = $this->projectDir . '/var/google_token.json';
            
            if (file_exists($tokenPath)) {
                try {
                    error_log("[DEBUG] Found token file, loading it");
                    $accessToken = json_decode(file_get_contents($tokenPath), true);
                    $this->client->setAccessToken($accessToken);
                    
                    // Check if token is expired and can be refreshed
                    if ($this->client->isAccessTokenExpired()) {
                        error_log("[DEBUG] Loaded token is expired, attempting to refresh");
                        try {
                            $this->refreshToken();
                        } catch (\Exception $e) {
                            error_log("[ERROR] Failed to refresh loaded token: " . $e->getMessage());
                            return false;
                        }
                    }
                    
                    // Make sure the calendarService is initialized
                    if (!$this->calendarService) {
                        $this->calendarService = new Google_Service_Calendar($this->client);
                    }
                    
                    return true;
                } catch (\Exception $e) {
                    error_log("[ERROR] Failed to load token from file: " . $e->getMessage());
                    return false;
                }
            } else {
                error_log("[WARN] No token file found at " . $tokenPath);
                return false;
            }
        }
        
        return false;
    }
} 