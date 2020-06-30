<?php

namespace AMREU\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class UserPromoteCommand extends Command
{
    private $repo;
    private $em;

    protected static $defaultName = 'app:user:promote';

    public function __construct(\App\Repository\UserRepository $repo, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->repo = $repo;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a role to the user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username to the promoted')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to be asigned to the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        $user = $this->repo->findOneBy(['username' => $username]);
        if ($user) {
            $roles = $user->getRoles();
            $roles[] = $role;
            $user->setRoles(array_unique($roles));
            $this->em->persist($user);
            $this->em->flush();
            $io->success(sprintf('User %s has been successfully promoted to %s', $username, $role));
        } else {
            $io->error(sprintf('User %s not found', $username));
        }

        return 0;
    }
}
