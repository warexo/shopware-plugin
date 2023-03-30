<?php

namespace Warexo\Subscriber;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;

class OrderStateChangeSubscriber implements EventSubscriberInterface
{
    private EntityRepository $transactionRepository;
    private EntityRepository $orderRepository;

    public function __construct(
        EntityRepository $transactionRepository,
        EntityRepository $orderRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order_transaction.state_changed' => 'onOrderTransactionStateChange'
        ];
    }

    public function onOrderTransactionStateChange(StateMachineStateChangeEvent $event): void
    {
        $orderTransactionId = $event->getTransition()->getEntityId();

        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');

        $orderTransaction = $this->transactionRepository
            ->search($criteria, $event->getContext())
            ->first();

        if ($orderTransaction && $orderTransaction->getOrder()) {
            $orderId = $orderTransaction->getOrder()->getId();
            $this->orderRepository->update([
                [
                    'id' => $orderId,
                    'updatedAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            ], $event->getContext());
        }
    }
}