<?php

namespace App\Service;

use App\Entity\Partnership;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventReminders;
use Google_Service_Calendar_EventReminder;

class GoogleCalendarService
{
    private Google_Service_Calendar $calendarService;
    private Google_Client $client;

    public function __construct(string $credentialsPath)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('EvoPlanWeb Partnership Reminder');
        $this->client->setScopes([Google_Service_Calendar::CALENDAR]);
        
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
     * Set access token obtained from OAuth callback
     * 
     * @throws \Exception If the token is invalid or expired and cannot be refreshed
     */
    public function setAccessToken(array $accessToken): void
    {
        $this->client->setAccessToken($accessToken);
        
        // Refresh the token if needed
        if ($this->client->isAccessTokenExpired()) {
            // Check if we have a refresh token
            $refreshToken = $this->client->getRefreshToken();
            if (!$refreshToken) {
                throw new \Exception('Access token has expired and no refresh token is available. Please re-authenticate.');
            }
            
            try {
                $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                // Check for errors
                if (isset($newAccessToken['error'])) {
                    throw new \Exception('Error refreshing access token: ' . 
                        $newAccessToken['error'] . ' - ' . 
                        ($newAccessToken['error_description'] ?? 'No description'));
                }
            } catch (\Exception $e) {
                throw new \Exception('Failed to refresh access token: ' . $e->getMessage() . 
                    ' Please re-authenticate at /admin/google/auth');
            }
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
     * Check if a partnership end reminder exists in Google Calendar
     * 
     * @param Partnership $partnership
     * @param string $calendarId The calendar ID to check (default: primary)
     * @return bool True if reminder exists, false otherwise
     */
    public function partnershipEndReminderExists(Partnership $partnership, string $calendarId = 'primary'): bool
    {
        try {
            // Check token status first
            if (!$this->hasValidToken()) {
                return false;
            }
            
            $partner = $partnership->getIdPartner();
            $event = $partnership->getIdEvent();
            
            if (!$partner || !$event || !$partnership->getDateFin()) {
                return false;
            }
            
            $endDate = $partnership->getDateFin();
            
            // Calculate time range to search events
            $startDate = (clone $endDate)->modify('-7 days');
            $endDateSearch = (clone $endDate)->modify('+1 day');
            
            // Create a search query for events
            $optParams = [
                'timeMin' => $startDate->format('c'),
                'timeMax' => $endDateSearch->format('c'),
                'q' => 'Partnership End Reminder: ' . $event->getNom(),
                'singleEvents' => true,
            ];
            
            // Search for events
            $events = $this->calendarService->events->listEvents($calendarId, $optParams);
            
            // Check if any events match the partnership
            foreach ($events->getItems() as $event) {
                // If we find a matching event, the reminder already exists
                if (strpos($event->getSummary(), 'Partnership End Reminder:') !== false) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            // Log error but treat as not existing
            error_log('Error checking for partnership reminder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a calendar event for partnership ending reminder
     * 
     * @param Partnership $partnership
     * @param string $calendarId The calendar ID to create the event in (default: primary)
     * @return string|null Event ID if created successfully, null otherwise
     */
    public function createPartnershipEndReminder(Partnership $partnership, string $calendarId = 'primary'): ?string
    {
        try {
            // Check token status first
            if (!$this->hasValidToken()) {
                throw new \Exception('Invalid or expired authentication token. Please re-authenticate.');
            }
            
            $partner = $partnership->getIdPartner();
            $event = $partnership->getIdEvent();
            
            if (!$partner || !$event || !$partnership->getDateFin()) {
                return null;
            }
            
            $endDate = $partnership->getDateFin();
            $partnerEmail = $partner->getEmail();
            
            // Create event
            $calendarEvent = new Google_Service_Calendar_Event();
            $calendarEvent->setSummary('Partnership End Reminder: ' . $event->getNom());
            $calendarEvent->setDescription(
                'Your partnership for event "' . $event->getNom() . '" is ending on ' . 
                $endDate->format('Y-m-d') . ". Please contact the organizers if you wish to extend the partnership."
            );
            
            // Set event date to the partnership end date
            $eventDateTime = new Google_Service_Calendar_EventDateTime();
            // Create a timed event rather than an all-day event
            $startDate = clone $endDate;
            $startDate->setTime(9, 0); // 9:00 AM
            $endDate->setTime(10, 0);  // 10:00 AM
            $eventDateTime->setDateTime($startDate->format('c')); // ISO 8601 format
            $calendarEvent->setStart($eventDateTime);
            
            $endDateTime = new Google_Service_Calendar_EventDateTime();
            $endDateTime->setDateTime($endDate->format('c'));
            $calendarEvent->setEnd($endDateTime);
            
            // Add attendee (partner)
            $calendarEvent->setAttendees([
                ['email' => $partnerEmail]
            ]);
            
            // Set reminders properly
            $reminder = new Google_Service_Calendar_EventReminder();
            $reminder->setMethod('email');
            $reminder->setMinutes(1440); // 24 hours before
            
            $reminders = new Google_Service_Calendar_EventReminders();
            $reminders->setUseDefault(false);
            $reminders->setOverrides([$reminder]);
            
            $calendarEvent->setReminders($reminders);
            
            // Insert event to calendar
            $createdEvent = $this->calendarService->events->insert($calendarId, $calendarEvent);
            
            return $createdEvent->getId();
        } catch (\Exception $e) {
            // Log the error
            error_log('Failed to create calendar event: ' . $e->getMessage());
            throw $e;
        }
    }
} 