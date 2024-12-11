<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface; // Import the LoggerInterface

class NotificationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private $logger;

    #[Route('/', name: 'home')]
public function index(): Response
{
    // Fetch all notifications
    $notifications = $this->entityManager->getRepository(Notification::class)->findAllNotificationsWithUsers();

    return $this->render('notification/index.html.twig', [
        'notifications' => $notifications,  // Pass notifications to the template
    ]);
}

  
    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer,LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('/api/notification/new', methods: ['POST'])]
    public function newNotification(Request $request): JsonResponse
    {
    $data = json_decode($request->getContent(), true);

    // Check for required parameters
    if (!isset($data['userId'], $data['destination'], $data['smsDestination'], $data['whatsappDestination'], $data['message'], $data['mode'], $data['application'])) {
        return new JsonResponse(['message' => 'Missing parameters'], 400);
    }

    // Find the user by userId
    $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
    if (!$user) {
        return new JsonResponse(['message' => 'User not found'], 404);
    }

    // Create a new Notification entity
    $notification = new Notification();
    $notification->setDestination($data['destination']);
    $notification->setSmsDestination($data['smsDestination']);
    $notification->setWhatsappDestination($data['whatsappDestination']);
    $notification->setUserId($user);
    $notification->setMessage($data['message']);
    $notification->setStatus('new');
    $notification->setCreatedAt(new \DateTimeImmutable());
    $notification->setUpdatedAt(new \DateTime());
    $notification->setMode($data['mode']);
    $notification->setApplication($data['application']); // Set the application

    // Persist the new notification
    $this->entityManager->persist($notification);
    $this->entityManager->flush();

    // Return a success response with the notification ID
    return new JsonResponse(['status code' => 201, 'id' => $notification->getId(), 'message' => 'ok'], 201);
}

#[Route('/api/notification/send', methods: ['POST'])]
public function sendNotification(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $notificationId = $data['notificationId'] ?? null;

    if (!$notificationId) {
        return new JsonResponse(['message' => 'Notification ID is required'], 400);
    }

    $notification = $this->entityManager->getRepository(Notification::class)->find($notificationId);

    if (!$notification) {
        return new JsonResponse(['message' => 'Notification not found'], 404);
    }

    $responses = [];
    $emailSent = false;
    $message = $notification->getMessage();
    $mode = $notification->getMode();

    try {
        switch ($mode) {
            case 'email':
                $emailSent = $this->handleEmailNotification($notification, $message, $responses);
                break;
            case 'sms':
                $responses['sms'] = $this->sendSmsViaInfobip($notification->getSmsDestination(), $message)['message'];
                break;
            case 'whatsapp':
                $responses['whatsapp'] = $this->sendWhatsappViaInfobip($notification->getWhatsappDestination(), $message)['message'];
                break;
            case 'all':
                $emailSent = $this->handleEmailNotification($notification, $message, $responses);
                $responses['sms'] = $this->sendSmsViaInfobip($notification->getSmsDestination(), $message)['message'];
                $responses['whatsapp'] = $this->sendWhatsappViaInfobip($notification->getWhatsappDestination(), $message)['message'];
                break;
            default:
                return new JsonResponse(['message' => 'Invalid mode specified'], 400);
        }

        // Set notification status based on whether any notification was sent successfully
        $notification->setStatus($emailSent ? 'sent' : 'failed');
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Notifications processed', 'details' => $responses], 200);
    } catch (\Exception $e) {
        $this->logger->error('An error occurred: ' . $e->getMessage()); // Log the error
        return new JsonResponse(['message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}

private function handleEmailNotification($notification, $message, &$responses)
{
    if ($notification->getDestination()) {
        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@example.com')
                ->to($notification->getDestination())
                ->subject('New Notification')
                ->text($message);

            $this->mailer->send($email);
            $responses['email'] = 'Email sent successfully';
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            $responses['email'] = 'Email sending failed: ' . $e->getMessage();
        }
    }
    return false;
}

    private function sendSmsViaInfobip(string $to, string $text): array
    {
        $curl = curl_init();

        $payload = json_encode([
            'messages' => [
                [
                    'destinations' => [['to' => $to]],
                    'from' => 'Eneo',
                    'text' => $text,
                ],
            ],
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://z1dej2.api.infobip.com/sms/2/text/advanced',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: App bd7bc8fc572fa255f4ed0e6ec510decc-320d5707-3973-4000-8e68-1225e9b46060',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            return ['status' => 'success', 'message' => 'SMS sent successfully'];
        } else {
            return ['status' => 'error', 'message' => 'SMS sending failed: ' . $response];
        }
    }

    private function sendWhatsappViaInfobip(string $to, string $text): array
    {
        $curl = curl_init();

        $payload = json_encode([
            'messages' => [
                [
                    'destinations' => [['to' => $to]],
                    'from' => 'Eneo', // Replace with your WhatsApp sender ID
                    'text' => $text,
                ],
            ],
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://z1dej2.api.infobip.com/whatsapp/1/message/text', // Update to the correct endpoint for WhatsApp
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: App bd7bc8fc572fa255f4ed0e6ec510decc-320d5707-3973-4000-8e68-1225e9b46060',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            return ['status' => 'success', 'message' => 'WhatsApp message sent successfully'];
        } else {
            return ['status' => 'error', 'message' => 'WhatsApp sending failed: ' . $response];
        }
    }

    #[Route('/api/notification/status', methods: ['GET'])]
    public function getNotificationStatus(Request $request): JsonResponse
    {
        $notificationId = $request->query->get('notificationId');
        $notification = $this->entityManager->getRepository(Notification::class)->find($notificationId);

        if ($notification) {
            return new JsonResponse([
                'id' => $notification->getId(),
                'userId' => $notification->getUserId() ? $notification->getUserId()->getId() : null,
                'destination' => $notification->getDestination(),
                'message' => $notification->getMessage(),
                'status' => $notification->getStatus(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $notification->getUpdatedAt() ? $notification->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                'mode' => $notification->getMode(),
            ]);
        }

        return new JsonResponse(['message' => 'Notification not found'], 404);
    }
}