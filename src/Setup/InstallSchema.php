<?php
/**
 * Create DB schema.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Setup;

use Praxigento\Bonus\Loyalty\Lib\Entity\Cfg\Param;
use Praxigento\Bonus\Loyalty\Lib\Entity\Qualification;

class InstallSchema extends \Praxigento\Core\Setup\Schema\Base
{
    protected function _setup()
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Loyalty';
        $demPackage = $this->_toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Cfg/ Param */
        $entityAlias = Param::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Config/entity/Param');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Qualification */
        $entityAlias = Qualification::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Qualification');
        $this->_toolDem->createEntity($entityAlias, $demEntity);
    }


}