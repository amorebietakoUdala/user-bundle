<?php

namespace AMREU\UserBundle\Command;

use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

/**
 * @autor ibilbao ibilbao@amorebieta.eus
 *
 * This command assigns the given roles to the specified username
 * The roles must separated with spaces
 */

#[AsCommand(name: 'amreu:user:promote')]
class UserPromoteCommand extends Command
{
    private $manager;

    public function __construct(UserManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add roles to the user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username to the promoted')
            ->addArgument('roles', InputArgument::IS_ARRAY, 'Roles to be assigned to the user separated by spaces')
            ->setHelp(<<<'EOT'
            The <info>amreu:user:promote</info> command promotes a user assigning specifyed roles
            You can specify more than one role separated by spaces
              <info>php %command.full_name% <username> <roles></info>
              
                
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $roles = $input->getArgument('roles');
        if (empty($roles)) {
            $question = new Question('Please enter roles list separated by spaces [ROLE_1 ROLE_2]: ', 'ROLE_1 ROLE_2');
            $question->setTrimmable(true);
            $rolesString = $helper->ask($input, $output, $question);
            $roles = explode(' ', $rolesString);
        }
        try {
            $this->manager->promoteUser($username, $roles);
            $io->success(sprintf('User %s has been successfully promoted to %s', $username, implode(' ', $roles)));
        } catch (\Exception $e) {
            $io->error(sprintf('User %s could not be promoted: %s', $username, $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
