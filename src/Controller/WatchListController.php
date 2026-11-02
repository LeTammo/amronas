<?php

namespace App\Controller;

use App\Dto\ListFilter;
use App\Dto\ListOrder;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\ListType;
use App\Enum\TitleType;
use App\Form\WatchListType;
use App\Repository\ListEntryRepository;
use App\Repository\ListInviteRepository;
use App\Repository\WatchListRepository;
use App\Security\WatchListVoter;
use App\Service\ListPresenter;
use App\Service\WatchListManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WatchListController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/', name: 'app_list_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, WatchListRepository $lists): Response
    {
        return $this->render('watch_list/index.html.twig', ['lists' => $user->sortLists($lists->findForUser($user))]);
    }

    /** Saves the order the user dragged their lists into (overview and navigation). */
    #[Route('/lists/order', name: 'app_list_order', methods: ['POST'])]
    #[IsCsrfTokenValid('list-order')]
    public function order(#[CurrentUser] User $user, WatchListRepository $lists, #[MapRequestPayload] ListOrder $order): Response
    {
        $own = array_map(static fn (WatchList $list) => $list->getId(), $lists->findForUser($user));
        $user->setListOrder(array_values(array_intersect(array_map(intval(...), $order->ids), $own)));
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/lists/new', name: 'app_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request, #[CurrentUser] User $user): Response
    {
        $list = new WatchList($user);
        $form = $this->createForm(WatchListType::class, $list);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($list);
            $this->em->flush();

            return $this->redirectToRoute('app_list_show', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('watch_list/new.html.twig', ['form' => $form]);
    }

    #[Route('/lists/{id}', name: 'app_list_show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[IsGranted(WatchListVoter::VIEW, 'list')]
    public function show(WatchList $list, Request $request, #[CurrentUser] User $user, ListEntryRepository $entries, ListPresenter $presenter, #[MapQueryString] ListFilter $filter = new ListFilter()): Response
    {
        if (!$list->getIcon() && $this->isGranted(WatchListVoter::EDIT, $list)) {
            $this->addFlash('info', 'list.icon_missing|'.$list->getName());

            return $this->redirectToRoute('app_list_edit', ['id' => $list->getId()]);
        }

        // Turbo loads links in advance when the mouse hovers them; only a real visit may remember filters and sorting
        $prefetch = 'prefetch' === $request->headers->get('X-Sec-Purpose');
        $session = $request->getSession();
        $sessionKey = 'list_filter_'.$list->getId();
        if ($filter->f && !$prefetch) {
            $session->set($sessionKey, ['type' => $filter->type?->value, 'short' => $filter->short, 'genre' => $filter->genre]);
        } elseif ($saved = $session->get($sessionKey)) {
            $filter = new ListFilter(TitleType::tryFrom((string) $saved['type']), $saved['short'], $filter->sort, $filter->dir, $filter->view, $saved['genre'] ?? [], true);
        }
        if (ListType::Mixed !== $list->getType() && $filter->type) {
            $filter = new ListFilter(null, $filter->short, $filter->sort, $filter->dir, $filter->view, $filter->genre, true);
        }

        if (!$prefetch && ($filter->sort || $filter->dir || $filter->view)) {
            $filter->sort && $user->setSortField($filter->sort);
            $filter->dir && $user->setSortDescending('desc' === $filter->dir);
            $filter->view && $user->setListView($filter->view);
            $this->em->flush();
        }

        return $this->render('watch_list/show.html.twig', [
            'list' => $list,
            'filter' => $filter,
            'view' => $presenter->present($entries->findForList($list), $filter, $user, $list->getCountry()),
        ]);
    }

    /** List settings: name, type and country for editors, the people on the list, invites for the owner. */
    #[Route('/lists/{id}/edit', name: 'app_list_edit', methods: ['GET', 'POST'])]
    #[IsGranted(WatchListVoter::VIEW, 'list')]
    public function edit(Request $request, WatchList $list, WatchListManager $manager, ListInviteRepository $invites): Response
    {
        $form = null;
        if ($this->isGranted(WatchListVoter::EDIT, $list)) {
            $country = $list->getCountry();
            $form = $this->createForm(WatchListType::class, $list);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->em->flush();
                $this->addFlash('success', 'list.saved');
                if ($country !== $list->getCountry() && $queued = $manager->refreshMissingOffers($list)) {
                    $this->addFlash('info', 'list.country_changed|'.$queued);
                }

                return $this->redirectToRoute('app_list_show', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
            }
        } elseif ($request->isMethod('POST')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('watch_list/edit.html.twig', [
            'list' => $list,
            'form' => $form,
            'invites' => $this->isGranted(WatchListVoter::MANAGE, $list) ? $invites->findOpen($list) : [],
            'new_link' => $request->getSession()->getFlashBag()->get('invite_link')[0] ?? null,
        ]);
    }

    #[Route('/lists/{id}/delete', name: 'app_list_delete', methods: ['POST'])]
    #[IsGranted(WatchListVoter::MANAGE, 'list')]
    #[IsCsrfTokenValid('delete-list')]
    public function delete(WatchList $list): Response
    {
        $this->em->remove($list);
        $this->em->flush();
        $this->addFlash('success', 'list.deleted');

        return $this->redirectToRoute('app_list_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/lists/{id}/leave', name: 'app_list_leave', methods: ['POST'])]
    #[IsGranted(WatchListVoter::VIEW, 'list')]
    #[IsCsrfTokenValid('leave-list')]
    public function leave(WatchList $list, #[CurrentUser] User $user): Response
    {
        if ($member = $list->getMember($user)) {
            $list->removeMember($member);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_list_index', [], Response::HTTP_SEE_OTHER);
    }
}
