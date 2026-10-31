<?php

namespace App\Controller;

use App\Entity\ListEntry;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\Priority;
use App\Imdb\ImdbSearch;
use App\Repository\ListEntryRepository;
use App\Security\WatchListVoter;
use App\Service\WatchListManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EntryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WatchListManager $manager,
    ) {
    }

    /** Results for the search field, rendered into a turbo frame: matches on the list, plus IMDb results to add for editors. */
    #[Route('/lists/{id}/search', name: 'app_entry_search', methods: ['GET'])]
    #[IsGranted(WatchListVoter::VIEW, 'list')]
    public function search(
        WatchList $list,
        #[CurrentUser] User $user,
        ImdbSearch $search,
        ListEntryRepository $entries,
        #[Target('title_search')] RateLimiterFactoryInterface $limiter,
        #[MapQueryParameter] string $q = '',
    ): Response {
        $q = trim($q);
        $onList = $entries->findForList($list);
        $matches = [];
        foreach ($onList as $entry) {
            if (false !== mb_stripos($entry->getTitle()->getName(), $q) || false !== mb_stripos($entry->getTitle()->getOriginalName() ?? '', $q)) {
                $matches[$entry->getId()] = $entry;
            }
        }

        $results = [];
        if ($this->isGranted(WatchListVoter::EDIT, $list)) {
            if (!$limiter->create((string) $user->getId())->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
            $byImdbId = [];
            foreach ($onList as $entry) {
                $byImdbId[$entry->getTitle()->getImdbId()] = $entry;
            }
            foreach ($search->search($q, $list->getType()->titleType()) as $result) {
                if (isset($byImdbId[$result->imdbId])) {
                    $matches[$byImdbId[$result->imdbId]->getId()] = $byImdbId[$result->imdbId];
                } else {
                    $results[] = $result;
                }
            }
        }

        return $this->render('entry/_search_results.html.twig', [
            'list' => $list,
            'query' => $q,
            'matches' => \array_slice(array_values($matches), 0, 5),
            'results' => $results,
        ]);
    }

    #[Route('/lists/{id}/entries', name: 'app_entry_add', methods: ['POST'])]
    #[IsGranted(WatchListVoter::EDIT, 'list')]
    #[IsCsrfTokenValid('entry')]
    public function add(WatchList $list, #[CurrentUser] User $user, Request $request, ImdbSearch $search): Response
    {
        $imdbId = $request->request->getString('imdbId');
        $results = $search->search($request->request->getString('q'), $list->getType()->titleType());
        $result = array_find($results, static fn ($r) => $r->imdbId === $imdbId);

        if (!$result) {
            $this->addFlash('error', 'entry.not_found');
        } elseif ($this->manager->add($list, $result, $user)) {
            $this->addFlash('success', 'entry.added|'.$result->name);
        } else {
            $this->addFlash('info', 'entry.already_added|'.$result->name);
        }

        return $this->redirectToRoute('app_list_show', ['id' => $list->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/entries/{id}/priority', name: 'app_entry_priority', methods: ['POST'])]
    #[IsCsrfTokenValid('entry')]
    public function priority(ListEntry $entry, Request $request): Response
    {
        $this->denyAccessUnlessGranted(WatchListVoter::EDIT, $entry->getList());
        $entry->setPriority(Priority::tryFrom($request->request->getString('priority')) ?? Priority::Normal);
        $this->em->flush();

        return $this->back($request, $entry);
    }

    #[Route('/entries/{id}/watched', name: 'app_entry_watched', methods: ['POST'])]
    #[IsCsrfTokenValid('entry')]
    public function watched(ListEntry $entry, Request $request): Response
    {
        $this->denyAccessUnlessGranted(WatchListVoter::EDIT, $entry->getList());
        $entry->setWatchedAt($entry->isWatched() ? null : new \DateTimeImmutable());
        $this->em->flush();

        return $this->back($request, $entry);
    }

    #[Route('/entries/{id}/refresh', name: 'app_entry_refresh', methods: ['POST'])]
    #[IsCsrfTokenValid('entry')]
    public function refresh(ListEntry $entry, Request $request): Response
    {
        $this->denyAccessUnlessGranted(WatchListVoter::EDIT, $entry->getList());
        $this->manager->refresh($entry->getTitle(), $entry->getList());
        $this->addFlash('info', 'entry.refreshing|'.$entry->getTitle()->getName());

        return $this->back($request, $entry);
    }

    #[Route('/entries/{id}/remove', name: 'app_entry_remove', methods: ['POST'])]
    #[IsCsrfTokenValid('entry')]
    public function remove(ListEntry $entry, Request $request): Response
    {
        $this->denyAccessUnlessGranted(WatchListVoter::EDIT, $entry->getList());
        $this->em->remove($entry);
        $this->em->flush();
        $this->addFlash('success', 'entry.removed|'.$entry->getTitle()->getName());

        return $this->back($request, $entry);
    }

    /** Back to the list page the action came from, keeping its filters. */
    private function back(Request $request, ListEntry $entry): Response
    {
        $list = $this->generateUrl('app_list_show', ['id' => $entry->getList()->getId()]);
        $return = $request->request->getString('return');

        return $this->redirect(str_starts_with($return, $list) ? $return : $list, Response::HTTP_SEE_OTHER);
    }
}
