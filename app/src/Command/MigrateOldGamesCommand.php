<?php

namespace App\Command;

use App\Entity\WordleGame;
use App\Repository\UserRepository;
use App\Repository\WordleGuessRepository;
use App\Repository\WordleSolutionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-old-games', description: 'Migrate old games to new one')]
class MigrateOldGamesCommand extends Command
{
    protected EntityManagerInterface $entityManager;
    protected UserRepository $userRepository;
    protected WordleGuessRepository $guessRepository;
    protected WordleSolutionRepository $solutionRepository;


    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, WordleGuessRepository $guessRepository, WordleSolutionRepository $solutionRepository)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->guessRepository = $guessRepository;
        $this->solutionRepository = $solutionRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->entityManager->getConnection();

        // migrate old wordle games
        $output->writeln("preparing games");
        $oldGames = $conn->prepare('SELECT * FROM wordle_game_old')->executeQuery()->fetchAllAssociative();
        $output->writeln("games prepared");
        foreach ($oldGames as $oldGame) {
            $user = $this->userRepository->findOneByOldId($oldGame['user_id']);
            if ($user === null) {
                $id = $oldGame['user_id'];
                $output->writeln("user $id not found");
                continue;
            }
            $solution = $this->solutionRepository->findOneByOldId($oldGame['solution_id']);
            $game = new WordleGame($solution);
            $game->setOldId($oldGame['id']);
            $game->setPlayer($user);
            $game->setCreatedAt(new DateTime($oldGame['created_at']));
            $game->setIsSolved($oldGame['is_solved']);
            $game->setIsFinished($oldGame['is_finished']);

            $this->entityManager->persist($game);
        }

        $count = count($oldGames);
        $output->writeln("$count games done");
        $output->writeln("flushing");
        $this->entityManager->flush();
        $output->writeln("done\n");

        return Command::SUCCESS;
    }
}