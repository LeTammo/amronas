<?php

namespace App\Command;

use App\Entity\WordleSolution;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-old-solutions', description: 'Migrate old solutions to new one')]
class MigrateOldSolutionsCommand extends Command
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

        // migrate old wordle solutions
        $output->writeln("preparing solutions");
        $oldSolutions = $conn->prepare('SELECT * FROM wordle_solution_old')->executeQuery()->fetchAllAssociative();
        $output->writeln("solutions prepared");
        foreach ($oldSolutions as $oldSolution) {
            $solution = new WordleSolution($oldSolution['correct_word'], new DateTime($oldSolution['created_at']));
            $solution->setOldId($oldSolution['id']);

            $this->entityManager->persist($solution);
        }
        $count = count($oldSolutions);
        $output->writeln("$count solutions done");
        $output->writeln("flushing");
        $this->entityManager->flush();
        $output->writeln("done\n");

        return Command::SUCCESS;
    }
}