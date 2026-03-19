<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class DecimalQuantityAddToCartFlashSubscriber implements EventSubscriberInterface
{
    private const ADD_ROUTE = 'frontend.checkout.line-item.add';

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->get('_route') !== self::ADD_ROUTE || !$request->hasSession()) {
            return;
        }

        $decimalAddCount = DecimalQuantityRequestSubscriber::getDecimalAddCount($request);
        if ($decimalAddCount === null) {
            return;
        }

        $session = $request->getSession();
        if (!$session instanceof SessionInterface) {
            return;
        }

        $flashBag = $session->getBag('flashes');
        if (!$flashBag instanceof FlashBagInterface) {
            return;
        }

        if (!$flashBag->has('success')) {
            return;
        }

        $formatter = new \NumberFormatter($request->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);

        $formattedCount = $formatter->format($decimalAddCount);
        if ($formattedCount === false) {
            $formattedCount = rtrim(rtrim(number_format($decimalAddCount, 3, '.', ''), '0'), '.');
        }

        $flashBag->set('success', [
            $this->translator->trans('checkout.addToCartSuccess', ['%count%' => $formattedCount]),
        ]);
    }
}