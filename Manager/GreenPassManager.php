<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Stadline\Resamania2Bundle\Lib\DataManager\Manager\DataManager;
use Stadline\Resamania2Bundle\Lib\DataManager\Referential\Resource;
use Stadline\Resamania2Bundle\Lib\Date\Service\DateManipulator;
use Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Entity\GreenPass;
use Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Repository\GreenPassRepository;

class GreenPassManager
{
    private EntityManagerInterface $manager;
    private GreenPassRepository $repository;
    private DataManager $dataManager;

    public function __construct(
        EntityManagerInterface $manager,
        GreenPassRepository $repository,
        DataManager $dataManager
    ) {
        $this->manager = $manager;
        $this->repository = $repository;
        $this->dataManager = $dataManager;
    }

    public function create(string $contactId, string $interval = GreenPass::GLOBAL_INTERVAL): GreenPass
    {
        // Check if contact exists
        $this->dataManager->getResource(Resource::CONTACTS, $contactId);

        $greenPass = new GreenPass();
        $greenPass->setContactId($contactId);
        $greenPass->setValidThrough($this->getExpirationDate($interval));

        $this->manager->persist($greenPass);
        $this->manager->flush();

        return $greenPass;
    }

    public function delete(string $contactId): void
    {
        $greenPasses = $this->repository->findBy(['contactId' => $contactId]);

        foreach ($greenPasses as $greenPass) {
            $this->manager->remove($greenPass);
        }

        $this->manager->flush();
    }

    public function removeExpired(\DateTime $date = null): int
    {
        $filters = $this->manager->getFilters();

        if ($filters->isEnabled('client_token_aware')) {
            $filters->disable('client_token_aware');
        }

        $deleteGreenPasses = $this->repository->removeAllExpired($date);

        if (!$filters->isEnabled('client_token_aware')) {
            $filters->enable('client_token_aware');
        }

        return $deleteGreenPasses;
    }

    public function getContactGreenPass(string $contactId): ?GreenPass
    {
        return $this->repository->findContactGreenPass($contactId);
    }

    public function delayContactGreenPass(string $contactId, string $interval): void
    {
        if (null === $greenPass = $this->getContactGreenPass($contactId)) {
            return;
        }

        // If it has been already edited (not +72H), do nothing
        if ($this->isAlreadyDelayed($greenPass)) {
            return;
        }

        $greenPass->setValidThrough($this->getExpirationDate($interval));

        $this->manager->persist($greenPass);
        $this->manager->flush();
    }

    private function isAlreadyDelayed(GreenPass $greenPass): bool
    {
        $originalValidThrough = $this->getExpirationDate(GreenPass::GLOBAL_INTERVAL, $greenPass->getCreatedAt());

        return $greenPass->getValidThrough() < $originalValidThrough;
    }

    private function getExpirationDate(string $interval, \DateTime $date = null): \DateTime
    {
        return DateManipulator::add($date ?? DateManipulator::createNow(), new \DateInterval($interval));
    }
}
