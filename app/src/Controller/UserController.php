<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\WordleGameRepository;
use App\Repository\WordleGuessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/takeover', name: 'app_user_takeover', methods: ['GET'])]
    public function takeover(User $user): Response
    {

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        UserRepository $userRepository,
        WordleGameRepository $gameRepository,
        WordleGuessRepository $guessRepository
    ): Response
    {
        foreach ($gameRepository->findBy(['player' => $user->getId()]) as $game) {
            foreach ($game->getGuesses() as $guess) {
                $guessRepository->remove($guess, true);
            }
            $gameRepository->remove($game, true);
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/change/username', name: 'app_user_change_username', methods: ['POST'])]
    public function changeUsername(Request $request, UserRepository $userRepository): Response
    {
        $username = $_POST['_username'];

        if ($userRepository->findOneBy(['username' => $username])) {
            $this->addFlash('error', ['text' => sprintf("Der Username '%s' existiert bereits, bitte wähle einen anderen...", $username)]);
            return $this->redirectToRoute('app_profile');
        }

        $user = $userRepository->find($request->get('user'));
        $user->setUsername($username);

        $userRepository->save($user, true);

        $this->addFlash('success', ['text' => sprintf("Dein Username wurde erfolgreich in '%s' geändert", $username)]);

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/change/password', name: 'app_user_change_password', methods: ['POST'])]
    public function changePassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHashService): Response
    {
        $user = $userRepository->find($request->get('user'));
        $user->setPassword($userPasswordHashService->hashPassword($user, $_POST['_password1']));

        $userRepository->save($user, true);

        $this->addFlash('success', ['text' => "Dein Password wurde erfolgreich geändert"]);

        return $this->render('user/profile.html.twig');
    }
}
