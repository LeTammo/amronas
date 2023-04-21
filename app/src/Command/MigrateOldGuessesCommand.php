<?php

namespace App\Command;

use App\Entity\WordleGame;
use App\Entity\WordleGuess;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-old-guesses', description: 'Migrate old guesses to new one')]
class MigrateOldGuessesCommand extends Command
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

        // migrate old wordle guesses
        $output->writeln("preparing guesses");
        $oldGuesses = $conn->prepare('SELECT * FROM wordle_guess_old')->executeQuery()->fetchAllAssociative();
        $output->writeln("guesses prepared");
        foreach ($oldGuesses as $oldGuess) {
            $game = $this->entityManager->getRepository(WordleGame::class)->findOneByOldId($oldGuess['game_id']);
            if (!$game) {
                $gameId = $oldGuess['game_id'];
                $output->writeln("game $gameId not found");
                continue;
            }
            $guess = new WordleGuess($game, $oldGuess['guessed_word'], $oldGuess['is_correct']);
            preg_match('/"(.*)";b:(\d)/', $oldGuess['info'], $matches);
            $info = [$matches[1] => $matches[2]];
            preg_match_all('/i:\d;s:\d:"(.*?)"/', $oldGuess['info'], $matches);
            foreach ($matches[1] as $key => $match) {
                $info[$key] = $match;
            }
            $guess->setOldId($oldGuess['id']);
            $guess->setInfo($info);
            $guess->setCreatedAt(new DateTime($oldGuess['created_at']));

            $this->entityManager->persist($guess);
        }
        $count = count($oldGuesses);
        $output->writeln("$count guesses done");
        $output->writeln("flushing");
        $this->entityManager->flush();
        $output->writeln("done\n");

        return Command::SUCCESS;
    }
}