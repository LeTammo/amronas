<?php

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\ListEntry;
use App\Entity\Title;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\DetailsSource;
use App\Enum\Priority;
use App\Message\RefreshProviders;
use App\Message\RefreshTitle;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class WatchListTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $schema = new SchemaTool($this->em);
        $schema->dropDatabase();
        $schema->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $container->get(AppFixtures::class)->load($this->em);
        $this->em->clear();
    }

    public function testGuestsAreSentToLogin(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testListShowsGroupsWithWatchedFoldedAtTheBottom(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');

        $crawler = $this->client->request('GET', '/lists/'.$list->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Unsere Filmliste');
        self::assertSame(['important', 'waiting', 'normal', 'maybe', 'watched'], $crawler->filter('details.group > summary')->each(static fn ($s) => explode(' ', $s->attr('class'))[1]));
        self::assertNull($crawler->filter('details.group')->last()->attr('open'));
        self::assertStringContainsString('Kedi', $crawler->filter('details.group')->last()->text());

        $this->client->getCookieJar()->set(new Cookie('fold', $list->getId().'-watched.o,'.$list->getId().'-maybe.c'));
        $crawler = $this->client->request('GET', '/lists/'.$list->getId());
        self::assertNotNull($crawler->filter('details.group')->last()->attr('open'));
        self::assertNull($crawler->filter('details.group')->eq(3)->attr('open'));
    }

    public function testSearchAndAddTitleQueuesBackgroundRefresh(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');

        $crawler = $this->client->request('GET', '/lists/'.$list->getId().'/search?q=dune');
        self::assertResponseIsSuccessful();
        self::assertSame(['Dune: Part Two'], $crawler->filter('a.s-item .s-title')->each(static fn ($n) => $n->text()));
        self::assertCount(0, $crawler->filter('input[name=imdbId][value=tt15239678]'));
        self::assertStringEndsWith('?f=1#entry-'.$this->entryId($list, 'Dune: Part Two'), $crawler->filter('a.s-item')->attr('href'));

        $form = $crawler->filter('input[name=imdbId][value=tt1160419]')->closest('form')->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/lists/'.$list->getId());
        $title = $this->em->getRepository(Title::class)->findOneBy(['imdbId' => 'tt1160419']);
        self::assertSame('Dune', $title->getName());
        self::assertNotNull($this->em->getRepository(ListEntry::class)->findOneBy(['list' => $list, 'title' => $title]));

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $messages = array_map(static fn ($envelope) => $envelope->getMessage(), $transport->getSent());
        self::assertSame([RefreshTitle::class, RefreshProviders::class], array_map(get_class(...), $messages));
        self::assertSame(['de'], $messages[0]->countries);
    }

    public function testFiltersAreRememberedInTheSessionAndRatingIsTheDefaultSort(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $url = '/lists/'.$list->getId();

        $crawler = $this->client->request('GET', $url);
        self::assertSame('rating', $crawler->filter('select[name=sort] option[selected]')->attr('value'));
        self::assertSame('Hannibal', $crawler->filter('.group-label.important + .list .t-name a')->first()->text());

        $this->client->request('GET', $url.'?f=1&type=series');
        $crawler = $this->client->request('GET', $url);
        self::assertCount(1, $crawler->filter('.row'));

        $this->client->request('GET', $url.'?f=1');
        $crawler = $this->client->request('GET', $url);
        self::assertCount(10, $crawler->filter('.row'));
    }

    public function testAllListsAreInTheNavigationInTheOrderTheUserDraggedThemInto(): void
    {
        $ours = $this->login('robin')->list('Unsere Filmliste');
        $klaras = $this->list('Klaras Serien');
        $foreign = $this->list('Anime');

        $crawler = $this->client->request('GET', '/lists/'.$ours->getId());
        $tabs = $crawler->filter('.topbar .tab');
        self::assertSame(['Listen', 'Unsere Filmliste', 'Klaras Serien'], $tabs->each(static fn ($a) => $a->text()));
        self::assertSame('page', $tabs->eq(1)->attr('aria-current'));
        $token = $crawler->filter('.topbar .tabs')->attr('data-sortable-token-value');

        $this->client->request('POST', '/lists/order', ['_token' => 'wrong', 'ids' => [$klaras->getId(), $ours->getId()]]);
        self::assertFalse($this->client->getResponse()->isSuccessful());
        self::assertSame([], $this->em->getRepository(User::class)->findOneBy(['username' => 'robin'])->getListOrder());

        $this->client->request('POST', '/lists/order', ['_token' => $token, 'ids' => [$foreign->getId(), $klaras->getId(), $ours->getId()]]);
        self::assertResponseStatusCodeSame(204);
        $this->em->clear();
        self::assertSame([$klaras->getId(), $ours->getId()], $this->em->getRepository(User::class)->findOneBy(['username' => 'robin'])->getListOrder());

        $crawler = $this->client->request('GET', '/');
        self::assertSame(['Listen', 'Klaras Serien', 'Unsere Filmliste'], $crawler->filter('.topbar .tab')->each(static fn ($a) => $a->text()));
        self::assertSame(['Klaras Serien', 'Unsere Filmliste'], $crawler->filter('.list-card h2')->each(static fn ($h) => $h->text()));
    }

    public function testWatchedEntriesShowTheirDateAndKeepTheirActionsInTheMenu(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $crawler = $this->client->request('GET', '/lists/'.$list->getId());

        $kedi = $crawler->filter('#entry-'.$this->entryId($list, 'Kedi'));
        self::assertSame(new \DateTimeImmutable('-10 days')->format('d.m.Y'), $kedi->filter('.watched-on')->text());
        self::assertCount(0, $kedi->filter('.offers'));
        self::assertCount(0, $kedi->filter('.trailer'));
        self::assertCount(1, $kedi->filter('.menu form[action$="/watched"]'));

        $hannibal = $crawler->filter('#entry-'.$this->entryId($list, 'Hannibal'));
        self::assertCount(1, $hannibal->filter('.trailer'));
        self::assertCount(1, $hannibal->filter('.row-actions > form[action$="/watched"]'));
        self::assertCount(0, $hannibal->filter('.watched-on'));
    }

    public function testGenreFilterWithPillsAndCountsInThePhoneSheet(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $url = '/lists/'.$list->getId();
        $sheetTexts = static fn ($crawler) => $crawler->filter('#genre-sheet > a')->each(static fn ($a) => preg_replace('/\s+/', '', $a->text()));
        $pill = static fn ($crawler, string $name) => $crawler->filter('.genre-pills .genre-pill')->reduce(static fn ($p) => str_starts_with($p->text(), $name))->link();

        $crawler = $this->client->request('GET', $url.'?f=1&type=movie');
        self::assertSame('Drama', $crawler->filter('.genre-pills .genre-pill')->first()->text());
        self::assertSame('Drama6', $sheetTexts($crawler)[0]);
        self::assertContains('Doku1', $sheetTexts($crawler));
        self::assertContains('Krimi1', $sheetTexts($crawler));

        $crawler = $this->client->click($pill($crawler, 'Krimi'));
        self::assertSame(['Swimming with Sharks'], $crawler->filter('.row .t-name a')->each(static fn ($a) => $a->text()));
        self::assertSame('true', $crawler->filter('.genre-pills .genre-pill.on')->attr('aria-pressed'));
        self::assertSame('1 Genre', trim($crawler->filter('#genre-button')->text()));

        $crawler = $this->client->click($pill($crawler, 'Doku'));
        self::assertSame(['Swimming with Sharks', 'Kedi'], $crawler->filter('.row .t-name a')->each(static fn ($a) => $a->text()));
        self::assertSame(['crime', 'documentary'], $crawler->filter('.sorting input[name="genre[]"]')->each(static fn ($i) => $i->attr('value')), 'Sorting keeps the genres');

        $crawler = $this->client->request('GET', $url);
        self::assertCount(2, $crawler->filter('.row'), 'Genres are remembered like the other filters');
        self::assertCount(10, $this->client->request('GET', $url.'?f=1')->filter('.row'));
    }

    public function testListsWithoutIconAskForOneFirst(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $list->setIcon(null);
        $this->em->flush();

        $this->client->request('GET', '/lists/'.$list->getId());
        self::assertResponseRedirects('/lists/'.$list->getId().'/edit');

        $crawler = $this->client->followRedirect();
        $form = $crawler->filter('form[name=watch_list]')->form();
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(422);

        $form['watch_list[icon]'] = 'cat';
        $this->client->submit($form);
        self::assertResponseRedirects('/lists/'.$list->getId());
        $this->client->followRedirect();
        self::assertSelectorExists('.topbar .tab[aria-current] svg');
        self::assertSame('cat', $this->em->find(WatchList::class, $list->getId())->getIcon());
    }

    public function testPriorityToggle(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $crawler = $this->client->request('GET', '/lists/'.$list->getId());

        $form = $crawler->filter('#entry-'.$this->entryId($list, 'Bullet Train').' button[value=important]')->form();
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(Priority::Important, $this->em->find(ListEntry::class, $this->entryId($list, 'Bullet Train'))->getPriority());
    }

    public function testMembersOnlyGetTheirRights(): void
    {
        $list = $this->login('robin')->list('Klaras Serien');

        $crawler = $this->client->request('GET', '/lists/'.$list->getId());
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('form[action$="/watched"]'));
        self::assertCount(0, $crawler->filter('form[action$="/priority"]'));

        $crawler = $this->client->request('GET', '/lists/'.$list->getId().'/search?q=a');
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('a.s-item')->count());
        self::assertCount(0, $crawler->filter('form[action$="/entries"]'));

        $crawler = $this->client->request('GET', '/lists/'.$list->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('form[name=watch_list]'));
        self::assertCount(1, $crawler->filter('form[action$="/leave"]'));
        $this->client->request('POST', '/lists/'.$list->getId().'/edit');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/lists/'.$this->list('Anime')->getId());
        self::assertResponseStatusCodeSame(403);
    }

    public function testInviteLinkWorksExactlyOnce(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $crawler = $this->client->request('GET', '/lists/'.$list->getId().'/edit');
        $this->client->submit($crawler->selectButton('Link erstellen')->form());
        $link = $this->client->followRedirect()->filter('#invite-link')->attr('value');

        $this->login('erik');
        $crawler = $this->client->request('GET', $link);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('form.form-actions')->form());
        self::assertResponseRedirects('/lists/'.$list->getId());

        $this->em->clear();
        $list = $this->em->find(WatchList::class, $list->getId());
        self::assertContains('erik', $list->getMembers()->map(static fn ($m) => $m->getUser()->getUsername())->getValues());

        $this->login('klara');
        $this->client->request('GET', $link);
        self::assertResponseRedirects('/lists/'.$list->getId());
    }

    public function testOnlyTheOwnerInvitesAndDeletes(): void
    {
        $list = $this->login('klara')->list('Unsere Filmliste');

        $crawler = $this->client->request('GET', '/lists/'.$list->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('form[action$="/invites"]'));

        self::assertCount(1, $crawler->filter('form[name=watch_list]'));
        self::assertCount(0, $crawler->filter('form[action$="/delete"]'));
    }

    public function testHeaderShowsOnlyPeopleWhoCanEdit(): void
    {
        $list = $this->login('robin')->list('Klaras Serien');

        $crawler = $this->client->request('GET', '/lists/'.$list->getId());

        self::assertSame('klara', trim($crawler->filter('.heading .meta span[title]')->text()));
        self::assertGreaterThan(0, $crawler->filter('.offer')->count());
    }

    public function testListsNobodyElseIsOnDontShowPeople(): void
    {
        $this->login('robin');
        $own = new WatchList($this->user('robin'))->setName('Nur für mich')->setIcon('cat');
        $this->em->persist($own);
        $this->em->flush();

        $this->client->request('GET', '/lists/'.$own->getId());
        self::assertSelectorNotExists('.heading .meta span[title]');

        $crawler = $this->client->request('GET', '/');
        self::assertCount(0, $crawler->filter('.list-card')->reduce(static fn ($c) => str_contains($c->text(), 'Nur für mich'))->filter('.sub'));
        self::assertCount(2, $crawler->filter('.list-card .sub'));
    }

    public function testAdminPagesAreForAdminsOnly(): void
    {
        $this->login('klara');
        $this->client->request('GET', '/admin/users');
        self::assertResponseStatusCodeSame(403);

        $this->login('robin');
        $this->client->request('GET', '/admin/users');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'erik');
    }

    public function testTitlesWithFallbackDetailsExplainWhereTheyComeFrom(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $this->em->getRepository(Title::class)->findOneBy(['name' => 'Bullet Train'])->setDetailsSource(DetailsSource::Imdb);
        $this->em->getRepository(Title::class)->findOneBy(['name' => 'Hannibal'])->setDetailsSource(DetailsSource::Streaming);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/lists/'.$list->getId());

        self::assertStringContainsString('von IMDb', $crawler->filter('#entry-'.$this->entryId($list, 'Bullet Train').' .fallback .pop')->text());
        self::assertCount(0, $crawler->filter('#entry-'.$this->entryId($list, 'Hannibal').' .fallback'));
    }

    public function testHoveringAFilterLinkDoesNotRememberIt(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $url = '/lists/'.$list->getId();

        $crawler = $this->client->request('GET', $url);
        self::assertSame('false', $crawler->filter('.filters')->attr('data-turbo-prefetch'));
        self::assertSame('false', $crawler->filter('.toolbar')->attr('data-turbo-prefetch'));

        $this->client->request('GET', $url.'?f=1&genre[]=documentary&sort=name', server: ['HTTP_X_SEC_PURPOSE' => 'prefetch']);
        self::assertCount(1, $this->client->getCrawler()->filter('.row'));

        $crawler = $this->client->request('GET', $url);
        self::assertCount(10, $crawler->filter('.row'));
        self::assertSame('rating', $crawler->filter('select[name=sort] option[selected]')->attr('value'));
    }

    public function testSwitchingTheLanguageKeepsThePage(): void
    {
        $list = $this->login('robin')->list('Unsere Filmliste');
        $url = 'http://localhost/lists/'.$list->getId().'?f=1&genre[]=drama';

        $this->client->request('GET', '/locale/en', server: ['HTTP_REFERER' => $url]);
        self::assertResponseRedirects($url);
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.group-label', 'Important');
        self::assertSame('en', $this->em->getRepository(User::class)->findOneBy(['username' => 'robin'])->getLocale());
    }

    private function login(string $username): self
    {
        $this->client->loginUser($this->user($username));

        return $this;
    }

    private function user(string $username): User
    {
        return self::getContainer()->get(UserRepository::class)->findOneBy(['username' => $username]);
    }

    private function list(string $name): WatchList
    {
        return $this->em->getRepository(WatchList::class)->findOneBy(['name' => $name]);
    }

    private function entryId(WatchList $list, string $titleName): int
    {
        $title = $this->em->getRepository(Title::class)->findOneBy(['name' => $titleName]);

        return $this->em->getRepository(ListEntry::class)->findOneBy(['list' => $list, 'title' => $title])->getId();
    }
}
