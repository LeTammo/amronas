<?php

namespace App\Controller;

use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ServicesController extends AbstractController
{
    #[Route('/auth', name: 'app_auth', methods: ['GET'])]
    public function auth(): Response
    {
        LoggerService::log('auth', 'Trying to authenticate user');
        if ($this->isGranted('ROLE_USER') && $this->isGranted('ROLE_SERVICE')) {
            LoggerService::log('auth', 'User has role ROLE_USER an ROLE_SERVICE');
            if ($this->getUser()) {
                LoggerService::log('auth', 'Authenticated user: ' . $this->getUser()->getUsername());
            } else {
                LoggerService::log('auth', 'Authenticated user: anonymous');
            }
            return new Response('Authenticated', Response::HTTP_OK);
        } else {
            LoggerService::log('auth', 'User not authenticated');
            return new Response('Unauthorized', Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/auth-admin', name: 'app_auth_admin', methods: ['GET'])]
    public function authAdmin(): Response
    {
        LoggerService::log('auth', 'Trying to authenticate administrator');
        if ($this->isGranted('ROLE_ADMIN')) {
            LoggerService::log('auth', 'User has role ROLE_ADMIN');
            if ($this->getUser()) {
                LoggerService::log('auth', 'Authenticated admin: ' . $this->getUser()->getUsername());
            } else {
                LoggerService::log('auth', 'Authenticated user: anonymous');
            }
            return new Response('Authenticated', Response::HTTP_OK);
        } else {
            LoggerService::log('auth', 'Admin not authenticated');
            return new Response('Unauthorized', Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/slowly', name: 'app_slowly_index', methods: ['GET'])]
    public function index(HttpClientInterface $client): Response
    {
        $myStamps = json_decode(file_get_contents(__DIR__ . "/../../assets/slowly.json"));

        $response = $client->request('GET', "https://slowly.app/en/stampstore/time-machine/");
        $crawler = new Crawler($response->getContent());

        $stamps = [];
        foreach ($crawler->filter('tr') as $domElement) {
            $children = $domElement->childNodes;

            $img = $children[1]->getElementsByTagName('img')[0]->getAttribute('src');
            $filename = explode('/', $img);
            $name = str_replace('.png', '', end($filename));

            if (!in_array($name, $myStamps)) {
                $stamps[$name] = [
                    'name' => trim($children[3]->getElementsByTagName('div')[0]->textContent),
                    'img' => $img,
                ];
            }
        }

        return $this->render('slowly/stamps.html.twig', [
            'stamps' => $stamps,
        ]);
    }

    #[Route('/slowly/add', name: 'app_slowly_add', methods: ['GET', 'POST'])]
    public function addStamp(Request $request): Response
    {
        try {
            $myStamps = json_decode(file_get_contents(__DIR__ . "/../../assets/slowly.json"));
            $myStamps[] = $request->get('name');

            file_put_contents(__DIR__ . "/../../assets/slowly.json", json_encode($myStamps));

            $this->addFlash('success', sprintf('Stamp "%s" was successfully added', $request->get('name')));
            LoggerService::log('slowly', sprintf('Added stamp "%s"', $request->get('name')));

        } catch (\Exception $e) {
            $this->addFlash('success', sprintf('Stamp "%s" could not be added', $request->get('name')));
            LoggerService::log('slowly', sprintf('Error while trying to add stamp "%s"', $request->get('name')));
        }
        return $this->redirectToRoute('app_slowly_index');
    }
}
