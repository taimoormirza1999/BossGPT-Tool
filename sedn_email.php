 <!DOCTYPE html>
 <html>
 <head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Page Title</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <script src='main.js'></script>
 </head>
 <body>
    
 </body>
 </html><?php
require 'vendor/autoload.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

// AWS Credentials
$awsKey = "YOUR_AWS_ACCESS_KEY";
$awsSecret = "YOUR_AWS_SECRET_KEY";
$awsRegion = "us-east-1"; 
// Create SES Client
$SesClient = new SesClient([
    'version' => 'latest',
    'region' => $awsRegion,
    'credentials' => [
        'key'    => $awsKey,
        'secret' => $awsSecret,
    ],
]);

// Email Details
$senderEmail = "your-verified-email@example.com";
$recipientEmail = "recipient@example.com";
$subject = "Welcome to BossGPT!";
$bodyHtml = "<html><body><h1>Hello from AWS SES</h1><p>This is a test email.</p></body></html>";

try {
    $result = $SesClient->sendEmail([
        'Destination' => [
            'ToAddresses' => [$recipientEmail],
        ],
        'Message' => [
            'Body' => [
                'Html' => [
                    'Charset' => 'UTF-8',
                    'Data' => $bodyHtml,
                ],
            ],
            'Subject' => [
                'Charset' => 'UTF-8',
                'Data' => $subject,
            ],
        ],
        'Source' => $senderEmail,
    ]);

    echo "Email sent! Message ID: " . $result['MessageId'] . "\n";
} catch (AwsException $e) {
    echo "Email sending failed: " . $e->getMessage();
}
?>
