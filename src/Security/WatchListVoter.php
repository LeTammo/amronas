<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\WatchList;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, WatchList> */
final class WatchListVoter extends Voter
{
    public const VIEW = 'LIST_VIEW';
    /** Add, remove, prioritise and mark titles as watched, change name, type and country. */
    public const EDIT = 'LIST_EDIT';
    /** Invite people, change their rights and delete the list: owner only. */
    public const MANAGE = 'LIST_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof WatchList && \in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $subject->canView($user),
            self::EDIT => $subject->canEdit($user),
            self::MANAGE => $subject->isOwner($user),
        };
    }
}
