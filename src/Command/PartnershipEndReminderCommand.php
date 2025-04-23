<?php

namespace App\Command;

use App\Repository\PartnershipRepository;
use App\Service\GoogleCalendarService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:partnership:end-reminder',
    description: 'Send calendar reminders for partnerships ending in 7 days',
)]
class PartnershipEndReminderCommand extends Command
{
    private PartnershipRepository $partnershipRepository;
    private GoogleCalendarService $googleCalendarService;
    private string $projectDir;
    private bool $isAuthenticated = false;

    public function __construct(
        PartnershipRepository $partnershipRepository,
        GoogleCalendarService $googleCalendarService,
        string $projectDir
    ) {
        parent::__construct();
        $this->partnershipRepository = $partnershipRepository;
        $this->googleCalendarService = $googleCalendarService;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addOption('token-file', 't', InputOption::VALUE_REQUIRED, 'Path to Google access token file (JSON)', 'var/google_token.json')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force create reminders without authentication check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Partnership End Reminder');
        $io->section('Checking for partnerships ending in 7 days...');

        // Load access token if provided
        $tokenFile = $input->getOption('token-file');
        $tokenPath = $this->projectDir . '/' . $tokenFile;
        
        // Try to load token
        if (file_exists($tokenPath)) {
            try {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $this->googleCalendarService->setAccessToken($accessToken);
                $io->note('Using Google access token from: ' . $tokenPath);
                $this->isAuthenticated = true;
            } catch (\Exception $e) {
                $io->error('Failed to load access token: ' . $e->getMessage());
                $this->showAuthenticationHelp($io);
                
                if (!$input->getOption('force')) {
                    return Command::FAILURE;
                }
            }
        } else {
            $io->warning('No access token file found at: ' . $tokenPath);
            $this->showAuthenticationHelp($io);
            
            if (!$input->getOption('force')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Continue without authentication? (reminders will not be created) [y/N] ', false);
                
                if (!$helper->ask($input, $output, $question)) {
                    return Command::FAILURE;
                }
            }
        }

        // Calculate the date range (today + 7 days)
        $today = new \DateTime();
        $sevenDaysFromNow = (new \DateTime())->modify('+7 days');

        // Find partnerships ending in the next 7 days
        $endingPartnerships = $this->partnershipRepository->findPartnershipsEndingBetween($today, $sevenDaysFromNow);

        if (empty($endingPartnerships)) {
            $io->success('No partnerships ending in the next 7 days.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d partnerships ending in the next 7 days.', count($endingPartnerships)));

        // If not authenticated and not forcing, only show info without creating events
        if (!$this->isAuthenticated && !$input->getOption('force')) {
            foreach ($endingPartnerships as $partnership) {
                $partner = $partnership->getIdPartner();
                $event = $partnership->getIdEvent();
                
                $io->text(sprintf(
                    '<comment>Would create reminder for:</comment> "%s" and event "%s" ending on %s',
                    $partner ? $partner->getEmail() : 'Unknown',
                    $event ? $event->getNom() : 'Unknown',
                    $partnership->getDateFin() ? $partnership->getDateFin()->format('Y-m-d') : 'Unknown'
                ));
            }
            
            $io->warning('No reminders were created because authentication is required. Use --force to bypass this check.');
            return Command::SUCCESS;
        }

        $successCount = 0;
        $failedCount = 0;

        // Create calendar events for each partnership
        foreach ($endingPartnerships as $partnership) {
            $partner = $partnership->getIdPartner();
            $event = $partnership->getIdEvent();
            
            $io->text(sprintf(
                'Creating reminder for partnership between "%s" and event "%s" ending on %s',
                $partner ? $partner->getEmail() : 'Unknown',
                $event ? $event->getNom() : 'Unknown',
                $partnership->getDateFin() ? $partnership->getDateFin()->format('Y-m-d') : 'Unknown'
            ));

            try {
                $eventId = $this->googleCalendarService->createPartnershipEndReminder($partnership);
                
                if ($eventId) {
                    $io->text('<info>✓</info> Calendar event created (ID: ' . $eventId . ')');
                    $successCount++;
                } else {
                    $io->text('<error>✗</error> Failed to create calendar event');
                    $failedCount++;
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'UNAUTHENTICATED') !== false || 
                    strpos($e->getMessage(), '401') !== false) {
                    $io->error('Authentication error: You need to authorize the application with Google Calendar first');
                    $this->showAuthenticationHelp($io);
                    return Command::FAILURE;
                }
                
                $io->text('<error>✗</error> Error: ' . $e->getMessage());
                $failedCount++;
            }
        }

        if ($failedCount === 0) {
            $io->success(sprintf('Successfully created %d calendar reminders.', $successCount));
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf(
                'Created %d calendar reminders, but %d failed. Check the logs for more details.',
                $successCount,
                $failedCount
            ));
            return Command::FAILURE;
        }
    }
    
    private function showAuthenticationHelp(SymfonyStyle $io): void
    {
        $io->section('Authentication Instructions');
        $io->text([
            'You need to authenticate with Google Calendar before creating reminders:',
            '',
            '1. Visit <info>http://localhost:8000/admin/google/auth</info> in your browser to authorize this application',
            '2. After authorization, a token will be stored in <info>var/google_token.json</info>',
            '3. Run this command again with: <info>php bin/console app:partnership:end-reminder</info>',
            '',
            '<comment>Important:</comment> Since your Google project is in test mode, only authorized test users can authenticate.',
            'Make sure the partner email matches the test user email in your Google Cloud Console.'
        ]);
    }
}
