<?php

namespace App\Command;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendNotificationCommand extends Command
{
    protected static $defaultName = 'app:send-notifications';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notifications = $this->entityManager->getRepository(Notification::class)->findBy(['status' => 'new']);
        foreach ($notifications as $notification) {
            // Simulate sending notification
            $notification->setStatus('sent');
            $notification->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();
            $output->writeln("Sent notification ID: " . $notification->getId());
        }
        return Command::SUCCESS;
    }
}
