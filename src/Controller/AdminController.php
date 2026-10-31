<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use App\Repository\WatchListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_admin_users', methods: ['GET'])]
    public function index(UserRepository $users, WatchListRepository $lists): Response
    {
        $all = $users->findAllOrdered();

        return $this->render('admin/index.html.twig', [
            'users' => $all,
            'list_counts' => array_combine(array_map(static fn (User $u) => $u->getId(), $all), array_map(static fn (User $u) => \count($lists->findForUser($u)), $all)),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($password = $form->get('newPassword')->getData()) {
                $user->setPassword($hasher->hashPassword($user, $password));
            }
            $this->em->flush();
            $this->addFlash('success', 'admin.saved|'.$user->getUsername());

            return $this->redirectToRoute('app_admin_users', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('delete-user')]
    public function delete(User $user, #[CurrentUser] User $me): Response
    {
        if ($user === $me) {
            $this->addFlash('error', 'admin.cannot_delete_self');
        } else {
            $this->em->remove($user);
            $this->em->flush();
            $this->addFlash('success', 'admin.deleted|'.$user->getUsername());
        }

        return $this->redirectToRoute('app_admin_users', [], Response::HTTP_SEE_OTHER);
    }
}
