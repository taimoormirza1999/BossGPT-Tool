<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../env.php';
loadEnv(__DIR__ . '/../.env');
class GoogleCalendarManager
{
    private $client;
    private $service;

    public function __construct()
    {
        // Load environment variables (if using dotenv)
        if (function_exists('loadEnv')) {
            loadEnv(__DIR__ . '/../.env');
        }

        $this->client = new Google_Client();
        $this->client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->client->setRedirectUri($_ENV['GOOGLE_CALENDAR_REDIRECT_URI']);
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        if (isset($_SESSION['access_token'])) {
            $this->client->setAccessToken($_SESSION['access_token']);
            $this->service = new Google_Service_Calendar($this->client);
        }
    }

    public function isAuthenticated()
    {
        return $this->client->isAccessTokenExpired() === false && isset($_SESSION['access_token']);
    }

    public function authenticate()
    {
        $authUrl = $this->client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function createFixedEvent($summary, $description, $eventDate)
    {
        if (!$this->isAuthenticated()) {
            die("Not authorized with Google Calendar.");
        }

        $startTime = $eventDate . ' 15:00:00'; // 3:00 PM
        $endTime   = $eventDate . ' 16:00:00'; // 4:00 PM

        $event = new Google_Service_Calendar_Event([
            'summary'     => $summary,
            'description' => $description ?? 'Appointment via form.',
            'start' => [
                'dateTime' => date('c', strtotime($startTime)),
                'timeZone' => 'Asia/Dubai',
            ],
            'end' => [
                'dateTime' => date('c', strtotime($endTime)),
                'timeZone' => 'Asia/Dubai',
            ],
        ]);

        try {
            $created = $this->service->events->insert('primary', $event);
            echo "<h3>Appointment Created:</h3>";
            echo "<p><a href='" . $created->htmlLink . "' target='_blank'>" . $created->getSummary() . "</a></p>";
        } catch (Exception $e) {
            echo "Error creating appointment: " . $e->getMessage();
        }
    }
}
