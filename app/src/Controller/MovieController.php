<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieListEntry;
use App\Form\MovieType;
use App\Repository\MovieListEntryRepository;
use App\Repository\MovieRepository;
use App\Service\GoogleService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/movie')]
class MovieController extends AbstractController
{
    //#[Route('/', name: 'app_movie_index', methods: ['GET'])]
    public function index(MovieRepository $movieRepository): Response
    {
        return $this->render('movie/index.html.twig', [
            'movies' => $movieRepository->findAll(),
        ]);
    }

    //#[Route('/new', name: 'app_movie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MovieRepository $movieRepository): Response
    {
        $movie = new Movie();
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $movieRepository->save($movie, true);

            return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('movie/new.html.twig', [
            'movie' => $movie,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_movie_show', methods: ['GET'])]
    public function show(Movie $movie): Response
    {
        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
        ]);
    }

    //#[Route('/{id}/edit', name: 'app_movie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Movie $movie, MovieRepository $movieRepository): Response
    {
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $movieRepository->save($movie, true);

            return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('movie/edit.html.twig', [
            'movie' => $movie,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_movie_delete', methods: ['POST'])]
    public function delete(Request $request, Movie $movie, MovieRepository $movieRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$movie->getId(), $request->request->get('_token'))) {
            $movieRepository->remove($movie, true);
        }

        return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-provider', name: 'app_movie_list_toggle_provider', methods: ['GET'])]
    public function activateProvider(Request $request, MovieListEntryRepository $entryRepository, MovieRepository $movieRepository): Response
    {
        $entry = $entryRepository->find($request->get('id'));
        if ($entry->getMovie()->toggleProvider($request->get('provider'))) {
            $this->addFlash('success', ['text' => sprintf('Ahyoo, man kann "%s" wohl doch nicht auf %s anschauen :(', $entry->getMovie()->getName(), ucfirst($request->get('provider')))]);
        } else {
            $this->addFlash('success', ['text' => sprintf('Super, man kann "%s" auf %s anschauen :D', $entry->getMovie()->getName(), ucfirst($request->get('provider')))]);
        }
        $movieRepository->save($entry->getMovie(), true);

        return $this->redirectToRoute('app_movie_list', ['id' => $entry->getMovieList()->getId()], Response::HTTP_SEE_OTHER);
    }

    public function addTrailer(GoogleService $googleService): Response
    {
        LoggerService::log('movie', sprintf('Added trailer for movie )'));

        return $this->redirectToRoute('app_movie_list');
    }
}
