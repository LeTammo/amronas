<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, #[Autowire('%app.registration_enabled%')] bool $registrationEnabled): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_list_index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'registration_enabled' => $registrationEnabled,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall.');
    }

    /** Switches the language, for guests in the session and for users permanently. */
    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'de|en'], methods: ['GET'])]
    public function locale(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        $request->getSession()->set('_locale', $locale);
        if (($user = $this->getUser()) instanceof User) {
            $user->setLocale($locale);
            $em->flush();
        }

        $back = (string) $request->headers->get('referer');

        return $this->redirect(str_starts_with($back, $request->getSchemeAndHttpHost()) ? $back : $this->generateUrl('app_list_index'));
    }
}
