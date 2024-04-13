<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieList;
use App\Entity\MovieListEntry;
use App\Form\MovieListType;
use App\Repository\MovieListEntryRepository;
use App\Repository\MovieListRepository;
use App\Repository\MovieRepository;
use App\Service\GoogleService;
use App\Service\LoggerService;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/watchlist')]
class MovieListController extends AbstractController
{
    public function __construct(
        private readonly MovieRepository          $movieRepository,
        private readonly MovieListRepository      $listRepository,
        private readonly MovieListEntryRepository $entryRepository,
    )
    {
    }

    #[Route('/', name: 'app_movie_list_index', methods: ['GET'])]
    public function index(MovieListRepository $movieListRepository): Response
    {
        return $this->render('movie_list/index.html.twig', [
            'maintainedMovieLists' => $movieListRepository->findByMaintainer($this->getUser()),
            'subscribedMovieLists' => $movieListRepository->findBySubscriber($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_movie_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MovieListRepository $movieListRepository): Response
    {
        $movieList = new MovieList();
        $form = $this->createForm(MovieListType::class, $movieList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $movieList->addMaintainer($this->getUser());
            $movieListRepository->save($movieList, true);

            return $this->redirectToRoute('app_movie_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('movie_list/new.html.twig', [
            'movieList' => $movieList,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/list', name: 'app_movie_list', methods: ['GET'])]
    public function list(MovieList $movieList): Response
    {
        return $this->render('movie_list/list.html.twig', [
            'movieList' => $movieList,
            'movieListEntries' => $this->entryRepository->findByCustomOrder($movieList),
        ]);
    }

    #[Route('/{id}/show', name: 'app_movie_list_show', methods: ['GET'])]
    public function show(MovieList $movieList): Response
    {
        return $this->render('movie_list/show.html.twig', [
            'movieList' => $movieList,
        ]);
    }

    #[Route('/{id}/movie/add', name: 'app_movie_list_movie_add', methods: ['POST'])]
    public function addEntry(MovieList $movieList, Request $request, GoogleService $googleService): Response
    {
        if (!in_array($this->getUser(), $movieList->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Du bist nicht berechtigt, diese Liste zu bearbeiten');
            return $this->redirectToRoute('app_home');
        }

        $parameters = json_decode($request->getContent(), true);
        $movieListEntry = new MovieListEntry();
        $movieListEntry->setMovieList($movieList);

        if (!$movie = $this->movieRepository->findOneBy(['imdbId' => $parameters['id']])) {
            try {
                $movie = new Movie();
                $movie->setName($parameters['name']);
                $movie->setImdbId($parameters['id']);
                $movie->setYear($parameters['year']);
                $movie->setPosterUrl($parameters['posterUrl']);
                $movie->setGenre(explode(',', $parameters['genres']));

                $youtubeId = $googleService->getBestSearchResult($movie->getName(), $movie->getYear());
                if (str_contains($youtubeId, '&')) {
                    $youtubeId = substr($youtubeId, 0, strpos($youtubeId, '&'));
                }
                $movie->setTrailerYoutubeId($youtubeId);

                $this->movieRepository->save($movie, true);

            } catch (\Exception $e) {
                $this->addFlash('error', sprintf('Error: %s', $e->getMessage()));
                LoggerService::log('movie', sprintf('Error: %s', $e->getMessage()));
                return $this->redirectToRoute('app_movie_list', ['id' => $movieList->getId()]);
            }
        }

        $movieListEntry->setMovie($movie);
        $movieListEntry->setMovieList($movieList);
        $movieListEntry->setTimeAdded(new DateTime());
        $movieListEntry->setAddedBy($this->getUser());
        $this->entryRepository->save($movieListEntry, true);

        $this->addFlash('success', sprintf('Yesssss, "%s" merke ich mir :)', $movie->getName()));

        return $this->redirectToRoute('app_movie_list', ['id' => $movieList->getId()]);
    }

    #[Route('movie/{id}/delete', name: 'app_movie_list_movie_delete', methods: ['GET'])]
    public function deleteEntry(MovieListEntry $entry): Response
    {
        if (!in_array($this->getUser(), $entry->getMovieList()->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Nice try, aber du bist nicht berechtigt, diesen Eintrag zu löschen ;)');
            return $this->redirectToRoute('app_home');
        }

        $this->entryRepository->remove($entry, true);
        $this->addFlash('success', sprintf('Der Eintrag "%s" wurde aus der Liste entfernt', $entry->getMovie()->getName()));

        return $this->redirectToRoute('app_movie_list', ['id' => $entry->getMovieList()->getId()]);
    }

    #[Route('/{id}/invite-maintainer', name: 'app_movie_list_invite_maintainer', methods: ['GET'])]
    public function inviteMaintainer(MovieList $movieList): Response
    {
        if (!in_array($this->getUser(), $movieList->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Du bist nicht berechtigt, diese Liste zu bearbeiten');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('movie_list/invite_maintainer.html.twig', [
            'movieList' => $movieList,
        ]);
    }

    #[Route('/{id}/invite-subscriber', name: 'app_movie_list_invite_subscriber', methods: ['GET'])]
    public function inviteSubscriber(MovieList $movieList): Response
    {
        if (!in_array($this->getUser(), $movieList->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Du bist nicht berechtigt, diese Liste zu bearbeiten');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('movie_list/invite_subscriber.html.twig', [
            'movieList' => $movieList,
        ]);
    }

    #[Route('/movie/set-as-watched', name: 'app_movie_list_movie_set_watched', methods: ['POST'])]
    public function setAsWatched(Request $request): Response
    {
        $entry = $this->entryRepository->find($request->get('id'));

        if (!in_array($this->getUser(), $entry->getMovieList()->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Du bist nicht berechtigt, diese Liste zu bearbeiten');
            return $this->redirectToRoute('app_home');
        }

        try {
            $entry->setTimeWatched(new DateTime($request->get('date')));
            $this->entryRepository->save($entry, true);
            $this->addFlash('success', sprintf('Genial, "%s" wurde am %s angeschaut.', $entry->getMovie()->getName(), $request->get('date')));
        } catch (Exception $e) {
            $this->addFlash('error', sprintf('Irgendwas klappt mit "%s" nicht, versuch\'s nach dem Prinzip "23.03.2022" x)', $request->get('date')));
        }

        return $this->redirectToRoute('app_movie_list', ['id' => $entry->getMovieList()->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_movie_list_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MovieList $movieList): Response
    {
        if (!in_array($this->getUser(), $movieList->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Du bist nicht berechtigt, diese Liste zu bearbeiten');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(MovieListType::class, $movieList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->listRepository->save($movieList, true);

            return $this->redirectToRoute('app_movie_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('movie_list/edit.html.twig', [
            'movieList' => $movieList,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_movie_list_delete', methods: ['POST'])]
    public function delete(Request $request, MovieList $movieList): Response
    {
        if (!in_array($this->getUser(), $movieList->getMaintainer()->toArray())) {
            $this->addFlash('error', 'Ziemlich ugly, aber du bist nicht berechtigt, diese Liste zu löschen ;)');
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $movieList->getId(), $request->request->get('_token'))) {
            $this->listRepository->remove($movieList, true);
        }

        return $this->redirectToRoute('app_movie_list_index', [], Response::HTTP_SEE_OTHER);
    }
}
