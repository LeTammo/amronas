<?php

namespace App\Command;

use App\Entity\Movie;
use App\Entity\MovieList;
use App\Entity\MovieListEntry;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-old-movies', description: 'Migrate old movies to new one')]
class MigrateOldMoviesCommand extends Command
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
        $output->writeln("preparing movies");
        $oldMovies = $conn->prepare('SELECT * FROM movie_old')->executeQuery()->fetchAllAssociative();
        $output->writeln("movies prepared");

        $users = [
            $this->entityManager->getRepository(User::class)->findOneByUsername('annmarii'),
            $this->entityManager->getRepository(User::class)->findOneByUsername('tammo')
        ];

        $seriesList = new MovieList();
        $seriesList->setName('🎬 Unsere Serienliste');
        $seriesList->addMaintainer($users[0]);
        $seriesList->addMaintainer($users[1]);
        $this->entityManager->persist($seriesList);

        $movieList = new MovieList();
        $movieList->setName('🎥 Unsere Filmliste');
        $movieList->addMaintainer($users[0]);
        $movieList->addMaintainer($users[1]);
        $this->entityManager->persist($movieList);

        foreach ($oldMovies as $oldMovie) {
            $movie = new Movie();
            $movie->setName($oldMovie['name']);
            $movie->setImdbId($oldMovie['imdb_id']);
            $movie->setGenre(explode(',', $oldMovie['genre']));
            $movie->setYear($oldMovie['year']);
            $movie->setTrailerYoutubeId($oldMovie['trailer_link']);
            $movie->setPosterUrl($oldMovie['poster_url']);
            if ($oldMovie['stream_provider']) {
                $movie->setStreamProvider(explode(',', $oldMovie['stream_provider']));
            }

            $movieListEntry = new MovieListEntry();
            $movieListEntry->setMovie($movie);
            if ($oldMovie['type'] === 'series') {
                $movieListEntry->setMovieList($seriesList);
            } else {
                $movieListEntry->setMovieList($movieList);
            }
            $movieListEntry->setTimeAdded(new DateTime($oldMovie['created_at']));
            if ($oldMovie['time_watched'] !== "1970-08-16 00:00:00") {
                $movieListEntry->setTimeWatched(new DateTime($oldMovie['time_watched']));
            }
            $movieListEntry->setAddedBy($users[array_rand($users)]);

            $this->entityManager->persist($movie);
            $this->entityManager->persist($movieListEntry);
        }

        $count = count($oldMovies);
        $output->writeln("$count movies done");
        $output->writeln("flushing");
        $this->entityManager->flush();
        $output->writeln("done\n");

        return Command::SUCCESS;
    }
}