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

        $formatter = new \NumberFormatter($request->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 3);

        $formattedCount = $formatter->format($decimalAddCount);
        if ($formattedCount === false) {
            $formattedCount = rtrim(rtrim(number_format($decimalAddCount, 3, '.', ''), '0'), '.');
        }

        $flashes = $flashBag->peekAll();
        foreach ($flashes as $type => $messages) {
            $rewrittenMessages = [];

            foreach ($messages as $message) {
                if (!is_string($message)) {
                    $rewrittenMessages[] = $message;
                    continue;
                }

                if ($type === 'success') {
                    $message = $this->buildAddToCartSuccessMessage($decimalAddCount, $formattedCount);
                }

                $rewrittenMessages[] = $this->replaceScaledStockValues($message, DecimalQuantityRequestSubscriber::getDecimalPayloads($request), $formatter);
            }

            if ($rewrittenMessages !== $messages) {
                $flashBag->set($type, $rewrittenMessages);
            }
        }
    }

    private function formatTranslationCount(float $count): string
    {
        return rtrim(rtrim(number_format($count, 3, '.', ''), '0'), '.');
    }

    private function buildAddToCartSuccessMessage(float $decimalAddCount, string $formattedCount): string
    {
        $translationCount = $this->formatTranslationCount($decimalAddCount);
        $message = $this->translator->trans('checkout.addToCartSuccess', ['%count%' => $translationCount]);

        if ($formattedCount === $translationCount) {
            return $message;
        }

        return str_replace($translationCount, $formattedCount, $message);
    }

    /**
     * @param array<string, array<string, mixed>> $decimalPayloads
     */
    private function replaceScaledStockValues(string $message, array $decimalPayloads, \NumberFormatter $formatter): string
    {
        foreach ($decimalPayloads as $payload) {
            $coreMaxPurchase = $payload['_warexoCoreMaxPurchase'] ?? null;
            $decimalMaxPurchase = $payload['warexoDecimalMaxPurchase'] ?? null;

            if ((!is_int($coreMaxPurchase) && !is_float($coreMaxPurchase)) || (!is_int($decimalMaxPurchase) && !is_float($decimalMaxPurchase))) {
                continue;
            }

            $formattedMaxPurchase = $formatter->format((float) $decimalMaxPurchase);
            if ($formattedMaxPurchase === false) {
                $formattedMaxPurchase = $this->formatTranslationCount((float) $decimalMaxPurchase);
            }

            $message = preg_replace(
                '/(?<![\d,.])' . preg_quote((string) (int) $coreMaxPurchase, '/') . '(?![\d,.])/',
                $formattedMaxPurchase,
                $message
            ) ?? $message;
        }

        return $message;
    }
}
