<?php

namespace AMREU\UserBundle\Command;

use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

/**
 * @autor ibilbao ibilbao@amorebieta.eus
 *
 * This command desactivates the specified user by his username
 * 
 */
class UserDeactivateCommand extends Command
{
    private $manager;

    protected static $defaultName = 'amreu:user:deactivate';

    public function __construct(UserManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deactivates the user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username to be deactivated')
            ->setHelp(<<<'EOT'
            The <info>amreu:user:activate</info> command deactivates a user
              <info>php %command.full_name% <username></info>
                
            EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        try {
            $this->manager->deactivateUser($username);
            $io->success(sprintf('User %s has been successfully deactivated', $username));
        } catch (\Exception $e) {
            $io->error(sprintf('User %s could not be activated: %s', $username, $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
