<?php

namespace App\Controller;

use App\Entity\ListInvite;
use App\Entity\ListMember;
use App\Entity\User;
use App\Entity\WatchList;
use App\Repository\ListInviteRepository;
use App\Security\WatchListVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Members are either "view only" or "can edit". Only the owner invites, changes rights and removes people. They're shown in the list settings. */
class MemberController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ListInviteRepository $invites,
    ) {
    }

    #[Route('/lists/{id}/members/{member}', name: 'app_member_update', methods: ['POST'])]
    #[IsGranted(WatchListVoter::MANAGE, 'list')]
    #[IsCsrfTokenValid('member')]
    public function update(WatchList $list, ListMember $member, Request $request): Response
    {
        if ($member->getList() !== $list) {
            throw $this->createNotFoundException();
        }

        if ($request->request->getBoolean('remove')) {
            $list->removeMember($member);
            $this->addFlash('success', 'member.removed|'.$member->getUser()->getUsername());
        } else {
            $member->setCanEdit('edit' === $request->request->getString('access'));
            $this->addFlash('success', 'member.saved|'.$member->getUser()->getUsername());
        }
        $this->em->flush();

        return $this->redirectToRoute('app_list_edit', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/lists/{id}/invites', name: 'app_invite_create', methods: ['POST'])]
    #[IsGranted(WatchListVoter::MANAGE, 'list')]
    #[IsCsrfTokenValid('member')]
    public function createInvite(WatchList $list, #[CurrentUser] User $user, Request $request): Response
    {
        $token = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');

        $this->em->persist(new ListInvite($list, $user, $token, 'edit' === $request->request->getString('access')));
        $this->em->flush();
        $this->addFlash('invite_link', $this->generateUrl('app_invite', ['token' => $token], 0));

        return $this->redirectToRoute('app_list_edit', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/invites/{id}/revoke', name: 'app_invite_revoke', methods: ['POST'])]
    #[IsCsrfTokenValid('member')]
    public function revokeInvite(ListInvite $invite): Response
    {
        $this->denyAccessUnlessGranted(WatchListVoter::MANAGE, $invite->getList());
        $this->em->remove($invite);
        $this->em->flush();

        return $this->redirectToRoute('app_list_edit', ['id' => $invite->getList()->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/invite/{token}', name: 'app_invite', methods: ['GET', 'POST'])]
    public function accept(string $token, #[CurrentUser] User $user, Request $request): Response
    {
        $invite = $this->invites->findByToken($token);
        $list = $invite?->getList();

        if ($list?->canView($user)) {
            return $this->redirectToRoute('app_list_show', ['id' => $list->getId()]);
        }
        if (!$invite?->isUsable()) {
            return $this->render('member/invite.html.twig', ['invite' => null], new Response(status: 404));
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('accept-invite', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $list->addMember($user, $invite->canEdit());
            $invite->markUsed($user);
            $this->em->flush();
            $this->addFlash('success', 'invite.joined|'.$list->getName());

            return $this->redirectToRoute('app_list_show', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('member/invite.html.twig', ['invite' => $invite, 'token' => $token]);
    }
}
