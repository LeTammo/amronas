<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:user:admin', 'Makes a user an admin (or removes the admin role with --revoke)')]
final readonly class PromoteUserCommand
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument] string $username, #[Option] bool $revoke = false): int
    {
        if (!$user = $this->users->findOneBy(['username' => $username])) {
            $io->error(\sprintf('There is no user "%s".', $username));

            return Command::FAILURE;
        }

        $user->setAdmin(!$revoke);
        $this->em->flush();
        $io->success(\sprintf('%s is %s an admin.', $username, $revoke ? 'no longer' : 'now'));

        return Command::SUCCESS;
    }
}
