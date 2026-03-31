<?php

namespace App\Controllers;

class GoogleController extends BaseController {

	public function index(){
		// Inside a method, e.g. public function googleTest()
$google = new \App\Services\GoogleService();

// 1. First-time auth (only needed once)
if (!$google->isAuthenticated()) {
    $authUrl = $google->getAuthUrl('https://yourdomain.com/php/google/callback');
    header('Location: ' . $authUrl);
    exit;
}

// 2. Create calendar event
$eventData = [
    'summary'     => 'Follow-up meeting',
    'description' => 'Discuss the attached file',
    'start'       => ['dateTime' => '2026-04-10T14:00:00+01:00', 'timeZone' => 'Europe/London'],
    'end'         => ['dateTime' => '2026-04-10T15:00:00+01:00', 'timeZone' => 'Europe/London'],
];
$result = $google->createCalendarEvent($eventData);

// 3. Send Gmail with a file from Google Drive
$gmailId = $google->sendGmailWithDriveAttachment(
    to: 'recipient@example.com',
    subject: 'Meeting notes + attached file from Drive',
    body: "Hi,\n\nPlease find the file attached.\n\nBest regards,\nYour App",
    driveFileId: '1abc123xyz...'   // ← your Drive file ID
);

$this->json([
    'calendar' => $result,
    'gmailId'  => $gmailId,
]);
	}
}
