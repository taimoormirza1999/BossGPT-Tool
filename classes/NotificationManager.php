<?php
require './vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
class NotificationManager
{
    private $userManager;
    public function __construct($userManager)
    {
        $this->userManager = $userManager;
    }
    public function sendProjectNotification($projectId = 42, $title = "DFs Title", $body = "DFs Body")
    {

        $users = $this->userManager->getProjectUsers($projectId);
        // echo "users: ".json_encode($users);
        if (empty($users)) {
            // echo "No users found for this project.";
            return "No users found for this project... " . $projectId;
        } else {
            // echo "users: ".json_encode($users);
        }

        $response_data = [];
        foreach ($users as $user) {
            $deviceToken = $user['fcm_token'];
            // echo "deviceToken: ".$deviceToken;
            if (empty($deviceToken)) {
                continue;
            }

            // Create notification 
            $notification = Notification::create($title, $body)
            ->withImageUrl('https://bossgpt.com/tool/v1/apple-touch-icon.png');
          

            // Store notification in database
            // $this->storeNotification($user['id'], $title, $body, $projectId);

            // Create Cloud Message 
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData([
                    'click_action' => 'https://bossgpt.com/notifications'
                ]);
                
            $factory = (new Factory())
                // ->withServiceAccount(__DIR__ . '/config/bossgpt-367ab-firebase-adminsdk-fbsvc-fdd178828e.json'  ); 
                ->withServiceAccount('./config/bossgpt-367ab-firebase-adminsdk-fbsvc-363f3ef6b7.json');
            $messaging = $factory->createMessaging();
            try {
                $messaging->send($message);
                $response_data[] = "Notification sent successfully to user with token $deviceToken!\n";
                // return "Notification sent successfully to user with token $deviceToken!\n";
            } catch (Exception $e) {
                $response_data[] = "Error sending notification to user with token $deviceToken: " . $e->getMessage();
                // echo "Error sending notification to user with token $deviceToken: " . $e->getMessage();
                // return "Error sending notification to user with token $deviceToken: " . $e->getMessage();
                // error_log("Error sending notification to user with token $deviceToken: " . $e->getMessage());
            }
        }
        return $response_data;
    }

}
