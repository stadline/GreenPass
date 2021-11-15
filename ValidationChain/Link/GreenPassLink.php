<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\ValidationChain\Link;

use ApiPlatform\Core\Api\IriConverterInterface;
use Stadline\Resamania2Bundle\Api\AccessControl\Entity\Entry;
use Stadline\Resamania2Bundle\Api\AccessControl\Entity\Identificator;
use Stadline\Resamania2Bundle\Lib\ClubNetwork\NetworkHeadersSwitch;
use Stadline\Resamania2Bundle\Lib\DataManager\Manager\DataManager;
use Stadline\Resamania2Bundle\Lib\DataManager\Referential\Mode;
use Stadline\Resamania2Bundle\Lib\DataManager\Referential\Resource;
use Stadline\Resamania2Bundle\Lib\GreenPass\Manager\GreenPassManager;
use Stadline\Resamania2Bundle\Lib\ValidationChain\Exception\ValidationChainObjectException;
use Stadline\Resamania2Bundle\Lib\ValidationChain\Link\AbstractValidationChainLink;
use Stadline\Resamania2Bundle\Lib\ValidationChain\ValidationChainContext;

class GreenPassLink extends AbstractValidationChainLink
{
    private const REASON = 'api.error.access-control.green-pass';

    private DataManager $dataManager;
    private GreenPassManager $manager;
    private NetworkHeadersSwitch $networkHeadersSwitch;
    private IriConverterInterface $iriConverter;

    public function __construct(
        DataManager $dataManager,
        GreenPassManager $manager,
        NetworkHeadersSwitch $networkHeadersSwitch,
        IriConverterInterface $iriConverter
    ) {
        $this->dataManager = $dataManager;
        $this->manager = $manager;
        $this->networkHeadersSwitch = $networkHeadersSwitch;
        $this->iriConverter = $iriConverter;
    }

    public function authorize(): ValidationChainContext
    {
        $entry = $this->context->getObject();

        if (!$entry instanceof Entry) {
            throw new ValidationChainObjectException(Entry::class, \get_class($entry));
        }

        // We need clubId, no matter if its from exit or entry zone
        $zone = $entry->getExitZone() ?? $entry->getEntryZone();

        $clubId = $zone->getClubId();
        $club = $this->dataManager->getResource(Resource::CLUBS, $clubId, Mode::ARRAY);

        if (null === ($greenPass = $club['configuration']['greenPass']) || !$greenPass) {
            return $this->context;
        }

        if ($entry->isManual() || ($entry->hasIdentificator() && $this->isEmployeeEntry($entry))) {
            return $this->context;
        }

        if (null === $this->manager->getContactGreenPass($entry->getTargetId())) {
            $this->context->setValidated(false);
            $this->context->setReason(self::REASON);
            $this->context->setEntryReason(self::REASON);

            return $this->context;
        }

        return $this->context;
    }

    private function isEmployeeEntry(Entry $entry): bool
    {
        $switch = $this->networkHeadersSwitch->switch($entry->getTargetId());

        $entryIdentificator = $entry->getIdentificator();
        /** @var Identificator $identificator */
        $identificator = $this->iriConverter->getItemFromIri($entryIdentificator['@id']);

        if (null !== $switch) {
            $this->networkHeadersSwitch->switchBack();
        }

        return $identificator->isEmployeeIdentificator();
    }

    public function getName(): string
    {
        return 'green_pass';
    }
}
