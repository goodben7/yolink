<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(private UserManager $um)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'user unique identifier (email)')
            ->addArgument('password', InputArgument::REQUIRED, 'user password')
            ->addArgument('role', InputArgument::REQUIRED, 'user role')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        if (!in_array($role, array_values(User::getAvailablesRoles()))) {
            $io->error(sprintf('The value "%s" is not a valid role', $role));
            
            return Command::INVALID;
        }

        try {
            $u = new User();
            $u->setEmail($email);
            $u->setRoles([$role]);
            $u->setPlainPassword($password);
            $u->setIsActivated(true);

            $this->um->create($u);

            $io->success('utilisateur créé avec succès !');

            return Command::SUCCESS;
        }
        catch(\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
