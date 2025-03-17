<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Pusher\Pusher;
use Dotenv\Dotenv;

// // Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();



class Notification {
    private static $pusher;

    // Initialize Pusher (Singleton)
    public static function init() {
        if (self::$pusher === null) {
            self::$pusher = new Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                ['cluster' => $_ENV['PUSHER_CLUSTER'], 'useTLS' => true]
            );
        }
        return self::$pusher;
    }

    // Send Notification
    public static function send($channel, $event, $data) {
        try {
            $pusher = self::init();
            $pusher->trigger($channel, $event, data: $data);
            return ['status' => 'success', 'message' => 'Notification sent'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

?>
