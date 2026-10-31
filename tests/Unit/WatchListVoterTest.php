<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Entity\WatchList;
use App\Security\WatchListVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class WatchListVoterTest extends TestCase
{
    public static function cases(): iterable
    {
        yield 'owner may manage' => ['owner', WatchListVoter::MANAGE, true];
        yield 'owner may edit' => ['owner', WatchListVoter::EDIT, true];
        yield 'editor may edit' => ['editor', WatchListVoter::EDIT, true];
        yield 'editor may not manage' => ['editor', WatchListVoter::MANAGE, false];
        yield 'viewer may view' => ['viewer', WatchListVoter::VIEW, true];
        yield 'viewer may not edit' => ['viewer', WatchListVoter::EDIT, false];
        yield 'stranger may not view' => ['stranger', WatchListVoter::VIEW, false];
    }

    #[DataProvider('cases')]
    public function testVote(string $who, string $attribute, bool $granted): void
    {
        $users = array_map(static fn (string $name) => new User()->setUsername($name), ['owner' => 'owner', 'editor' => 'editor', 'viewer' => 'viewer', 'stranger' => 'stranger']);
        $list = new WatchList($users['owner']);
        $list->addMember($users['editor'], true);
        $list->addMember($users['viewer'], false);

        $token = new UsernamePasswordToken($users[$who], 'main', ['ROLE_USER']);
        $vote = new WatchListVoter()->vote($token, $list, [$attribute]);

        self::assertSame($granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $vote);
    }
}
