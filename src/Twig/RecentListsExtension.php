<?php

namespace App\Twig;

use App\Entity\User;
use App\Entity\WatchList;
use App\Repository\WatchListRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFunction;

final readonly class RecentListsExtension
{
    public function __construct(
        private Security $security,
        private WatchListRepository $lists,
    ) {
    }

    /** @return list<WatchList> the lists the current user opened last and can still see */
    #[AsTwigFunction('recent_lists')]
    public function recentLists(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$ids = $user->getRecentLists()) {
            return [];
        }

        $byId = [];
        foreach ($this->lists->findBy(['id' => $ids]) as $list) {
            if ($list->isOwner($user) || $list->getMember($user)) {
                $byId[$list->getId()] = $list;
            }
        }

        return array_values(array_filter(array_map(static fn (int $id) => $byId[$id] ?? null, $ids)));
    }
}
