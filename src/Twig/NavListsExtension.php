<?php

namespace App\Twig;

use App\Entity\User;
use App\Entity\WatchList;
use App\Repository\WatchListRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFunction;

final readonly class NavListsExtension
{
    public function __construct(
        private Security $security,
        private WatchListRepository $lists,
    ) {
    }

    /** @return list<WatchList> all lists of the current user, in the order they dragged them into */
    #[AsTwigFunction('nav_lists')]
    public function navLists(): array
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user->sortLists($this->lists->findForUser($user)) : [];
    }
}
