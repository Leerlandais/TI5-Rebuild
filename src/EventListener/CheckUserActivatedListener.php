<?php

// src/EventListener/CheckUserActivatedListener.php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Routing\RouterInterface;

class CheckUserActivatedListener
{
    private $router;
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $user = $event->getUser();

        // Assuming 'activated' is a boolean field in the User entity
        if (!$user->isActivate()) {
            // Redirect to login with a flash message
            $request = $this->requestStack->getCurrentRequest();
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add('error', 'Your account is not activated. Please contact support.');

            $response = new RedirectResponse($this->router->generate('app_logout'));
            $event->setResponse($response);
        }
    }
}
