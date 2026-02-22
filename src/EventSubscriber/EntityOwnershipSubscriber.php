<?php

namespace App\EventSubscriber;

use App\Entity\Admin;
use App\Entity\Balance;
use App\Entity\Configuration;
use App\Entity\Currency;
use App\Entity\Deposit;
use App\Entity\Exchange;
use App\Entity\Income;
use App\Entity\Investment;
use App\Entity\Loan;
use App\Entity\Payment;
use App\Entity\Tag;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EntityOwnershipSubscriber implements EventSubscriberInterface
{
    private const DIRECTLY_OWNED = [
        Balance::class,
        Tag::class,
        Loan::class,
        Investment::class,
        Configuration::class,
        Currency::class,
    ];

    private const BALANCE_SCOPED = [
        Payment::class,
        Income::class,
        Deposit::class,
        Exchange::class,
    ];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCrudActionEvent::class => 'onBeforeCrudAction',
        ];
    }

    public function onBeforeCrudAction(BeforeCrudActionEvent $event): void
    {
        $context = $event->getAdminContext();

        if (!$context) {
            return;
        }

        $action = $context->getCrud()?->getCurrentAction();

        if (!in_array($action, ['detail', 'edit', 'delete'])) {
            return;
        }

        $entity = $context->getEntity()?->getInstance();

        if (!$entity) {
            return;
        }

        /** @var Admin $admin */
        $admin = $this->security->getUser();

        if (!$admin) {
            return;
        }

        $entityClass = get_class($entity);

        if (in_array($entityClass, self::DIRECTLY_OWNED)) {
            if ($entity->getAdmin() !== $admin && $entity->getAdmin()?->getId() !== $admin->getId()) {
                throw new AccessDeniedHttpException('Access denied.');
            }
        } elseif (in_array($entityClass, self::BALANCE_SCOPED)) {
            $balance = $this->getBalanceFromEntity($entity);

            if ($balance && $balance->getAdmin() !== $admin && $balance->getAdmin()?->getId() !== $admin->getId()) {
                throw new AccessDeniedHttpException('Access denied.');
            }
        }
    }

    private function getBalanceFromEntity(object $entity): ?Balance
    {
        if ($entity instanceof Payment || $entity instanceof Income || $entity instanceof Deposit) {
            return $entity->getBalance();
        }

        if ($entity instanceof Exchange) {
            return $entity->getBalanceFrom();
        }

        return null;
    }
}
