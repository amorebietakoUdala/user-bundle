<?php

namespace AMREU\UserBundle\Command;

use AMREU\UserBundle\Model\UserManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'amreu:user:create')]
class UserCreateCommand extends Command
{
    private $manager;

    public function __construct(UserManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:user:create')
            ->setDescription('Create a user')
            ->addArgument('username', InputArgument::REQUIRED, 'The new user\'s username (required)')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'The new user\'s first name (optional)')
            ->addArgument('email', InputArgument::OPTIONAL, 'The new user\'s email (optional)')
            ->addArgument('roles', InputArgument::IS_ARRAY, 'The new user\'s roles (optional)')
            ->setHelp(<<<'EOT'
            The <info>amreu:user:create</info> command creates a new user by specifying username, first name, email and roles
            You can specify more than one role separated by spaces
              <info>php %command.full_name% <username> <firstName> <email> <roles></info>


EOT
            )
       ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $email = $input->getArgument('email');
        $roles = $input->getArgument('roles');

        if (empty($firstName)) {
            $question = new Question('Please enter the firstname [John]: ', '');
            $question->setTrimmable(true);
            $firstName = $helper->ask($input, $output, $question);
        }
        if (empty($email)) {
            $question = new Question('Please enter the email: [youremail@yourdomain.com]: ', '');
            $question->setTrimmable(true);
            $email = $helper->ask($input, $output, $question);
        }
        if (empty($roles)) {
            $question = new Question('Please enter roles list separated by spaces [ROLE_1 ROLE_2]: ', '');
            $question->setTrimmable(true);
            $rolesString = $helper->ask($input, $output, $question);
            $roles = explode(' ', $rolesString);
        }

        $question = new Question('Please enter password: ', '');
        $question->setValidator(function ($password) {
            if (empty($password)) {
                throw new \Exception('Password can not be empty');
            }

            return $password;
        });
        $question->setHidden(true);
        $password = $helper->ask($input, $output, $question);

        try {
            $user = $this->manager->newUser($username, $password, $firstName, $email, $roles);
            $io->success('User '.$username.' succesfully created!');
        } catch (Exception $e) {
            $io->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
