<?php

namespace AMREU\UserBundle\Command;

use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @autor ibilbao ibilbao@amorebieta.eus
 *
 * This command assigns the given roles to the specified username
 * The roles must separated with spaces
 */
class UserDeleteCommand extends Command
{
    private $manager;

    protected static $defaultName = 'amreu:user:delete';

    public function __construct(UserManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes the user')
            ->addArgument('username', InputArgument::REQUIRED, 'User to be deleted')
            ->setHelp(<<<'EOT'
            The <info>amreu:user:delete</info> command deletes a user specifying it's username
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
            $this->manager->deleteUser($username);
            $io->success(sprintf('User %s has been successfully deleted', $username));
        } catch (\Exception $e) {
            $io->error(sprintf('User %s could not be deleted: %s', $username, $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
