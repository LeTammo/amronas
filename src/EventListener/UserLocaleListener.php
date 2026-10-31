<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Runs after the firewall: uses the language of the logged-in user (or the one picked on the login page)
 * and sends users who haven't done the first-login setup to the settings page.
 */
final readonly class UserLocaleListener
{
    private const SETUP_ALLOWED_ROUTES = ['app_settings', 'app_logout', 'app_locale', 'app_invite'];

    public function __construct(
        private Security $security,
        private LocaleSwitcher $localeSwitcher,
        private UrlGeneratorInterface $urls,
        #[Autowire('%kernel.enabled_locales%')]
        private array $locales,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 4)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();
        $locale = $user instanceof User ? $user->getLocale() : ($request->hasPreviousSession() ? $request->getSession()->get('_locale') : null);
        if (\in_array($locale, $this->locales, true)) {
            $request->setLocale($locale);
            $this->localeSwitcher->setLocale($locale);
        }

        $route = (string) $request->attributes->get('_route');
        if ($user instanceof User && !$user->isSetupCompleted() && !str_starts_with($route, '_') && !\in_array($route, self::SETUP_ALLOWED_ROUTES, true)) {
            $event->setResponse(new RedirectResponse($this->urls->generate('app_settings', ['welcome' => 1])));
        }
    }
}
