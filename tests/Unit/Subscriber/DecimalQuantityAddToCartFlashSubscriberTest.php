<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Subscriber\DecimalQuantityAddToCartFlashSubscriber;

#[CoversClass(DecimalQuantityAddToCartFlashSubscriber::class)]
final class DecimalQuantityAddToCartFlashSubscriberTest extends TestCase
{
    public function testScalesRoundedProductStockReachedQuantity(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('warning', 'Das Produkt "Meterware" ist nur noch 1200 mal verfügbar');
        $session->getFlashBag()->add('warning', 'Das Produkt "Stückware" ist nur noch 1500 mal verfügbar');

        $request = new Request();
        $request->setLocale('de-DE');
        $request->setSession($session);
        $request->attributes->set('_route', 'frontend.checkout.line-item.add');
        $request->attributes->set('warexoDecimalAddCount', 2.0);
        $request->attributes->set('warexoDecimalPayloads', [
            'decimal-product' => [
                'warexoIsDecimalQuantity' => true,
                'warexoDecimalMaxPurchase' => 1.234,
                '_warexoCoreMaxPurchase' => 1234.0,
                '_warexoProductName' => 'Meterware',
            ],
        ]);

        $subscriber = new DecimalQuantityAddToCartFlashSubscriber(
            $this->createTranslator(),
            new DecimalQuantityMapper()
        );
        $subscriber->onKernelResponse(new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        ));

        static::assertSame([
            'Das Produkt "Meterware" ist nur noch 1,2 mal verfügbar',
            'Das Produkt "Stückware" ist nur noch 1500 mal verfügbar',
        ], $session->getFlashBag()->peek('warning'));
    }

    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static function (string $id, array $parameters = []): string {
                if ($id !== 'checkout.product-stock-reached') {
                    return $id;
                }

                return strtr('Das Produkt "%name%" ist nur noch %quantity% mal verfügbar', $parameters);
            }
        );

        return $translator;
    }
}
