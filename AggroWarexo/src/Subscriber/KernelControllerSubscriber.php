<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class KernelControllerSubscriber
 * @package Warexo\Subscriber
 *
 * Allows sending auth token via get parameter warexoToken, used for framed widgets
 */
class KernelControllerSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \Symfony\Component\HttpKernel\KernelEvents::REQUEST => 'onKernelRequest',
            \Symfony\Component\HttpKernel\KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event)
    {
        $request = $event->getRequest();
        $token = $request->get('warexoToken');
        if ($token) {
            $warexoToken = base64_decode($request->get('warexoToken'));
            if ($warexoToken) {
                $event->getRequest()->attributes->set('warexoToken', $warexoToken);
            }
        }
    }

    public function onKernelResponse(\Symfony\Component\HttpKernel\Event\ResponseEvent $event)
    {
        $request = $event->getRequest();
        $warexoToken = $request->attributes->get('warexoToken');
        if ($warexoToken) {
            $response = $event->getResponse();
            $response->headers->setCookie(Cookie::create('bearerAuth')
                ->withValue($warexoToken)
                ->withPath('/admin')
                ->withSameSite('Strict')
                ->withHttpOnly(false)
                ->withSecure(false));
        }

    }
}