<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-old-users', description: 'Migrate old users to new one')]
class MigrateOldUsersCommand extends Command
{
    protected EntityManagerInterface $entityManager;


    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->entityManager->getConnection();

        // migrate old users
        $output->writeln("preparing users");
        $oldUsers = $conn->prepare('SELECT * FROM user_old')->executeQuery()->fetchAllAssociative();
        $output->writeln("users prepared");
        foreach ($oldUsers as $oldUser) {
            $user = new User();
            $user->setOldId($oldUser['id']);
            $user->setUsername($oldUser['username']);
            $user->setPassword($oldUser['password']);
            $user->setRoles(json_decode($oldUser['roles']));
            $user->setEmail("");

            $this->entityManager->persist($user);
        }
        $count = count($oldUsers);
        $output->writeln("$count users done");
        $output->writeln("flushing");
        $this->entityManager->flush();
        $output->writeln("done\n");

        return Command::SUCCESS;
    }
}