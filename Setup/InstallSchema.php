<?php
/**
 * Create DB schema.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Setup;

use Praxigento\BonusLoyalty\Repo\Entity\Data\Cfg\Param;
use Praxigento\BonusLoyalty\Repo\Entity\Data\Qualification;

class InstallSchema extends \Praxigento\Core\App\Setup\Schema\Base
{
    protected function setup()
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Loyalty';
        $demPackage = $this->toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Cfg/ Param */
        $entityAlias = Param::ENTITY_NAME;
        $demEntity = $demPackage->get('package/Config/entity/Param');
        $this->toolDem->createEntity($entityAlias, $demEntity);

        /* Qualification */
        $entityAlias = Qualification::ENTITY_NAME;
        $demEntity = $demPackage->get('entity/Qualification');
        $this->toolDem->createEntity($entityAlias, $demEntity);
    }


}