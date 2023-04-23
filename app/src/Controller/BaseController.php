<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class BaseController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/home', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/ich-arbeite-dran', name: 'app_at_construction')]
    public function atConstruction(): Response
    {
        return $this->render('at_construction.html.twig');
    }

    #[Route('/error', name: 'app_error')]
    public function error(): Response
    {
        return $this->redirectToRoute('app_at_construction');
        return $this->render('bundles/TwigBundle/Exception/error.html.twig');
    }
}