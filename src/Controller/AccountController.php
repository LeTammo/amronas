<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\SettingsFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, Security $security, #[Autowire('%app.registration_enabled%')] bool $registrationEnabled): Response
    {
        if (!$registrationEnabled) {
            throw $this->createNotFoundException('Registration is disabled.');
        }
        if ($this->getUser()) {
            return $this->redirectToRoute('app_list_index');
        }

        $user = new User()->setLocale($request->getLocale());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->em->persist($user);
            $this->em->flush();

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }

    #[Route('/settings', name: 'app_settings')]
    public function settings(Request $request, #[CurrentUser] User $user, #[MapQueryParameter] bool $welcome = false): Response
    {
        $setup = $welcome || !$user->isSetupCompleted();
        $form = $this->createForm(SettingsFormType::class, $user, ['setup' => $setup]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$setup && $newPassword = $form->get('newPassword')->getData()) {
                $user->setPassword($this->hasher->hashPassword($user, $newPassword));
            }
            $user->setSetupCompleted(true);
            $request->getSession()->set('_locale', $user->getLocale());
            $this->em->flush();
            $this->addFlash('success', 'settings.saved');

            return $this->redirectToRoute($setup ? 'app_list_index' : 'app_settings', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/settings.html.twig', ['form' => $form, 'setup' => $setup]);
    }
}
