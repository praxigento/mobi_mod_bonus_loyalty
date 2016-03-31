<?php
/**
 * Create DB schema.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Praxigento\Bonus\Loyalty\Lib\Entity\Cfg\Param;
use Praxigento\Bonus\Loyalty\Lib\Entity\Qualification;

class InstallSchema extends \Praxigento\Core\Setup\Schema\Base
{
    protected function _setup(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Loyalty';
        $demPackage = $this->_toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Cfg/ Param */
        $entityAlias = Param::ENTITY_NAME;
        $demEntity = $demPackage['package']['Config']['entity']['Param'];
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Qualification */
        $entityAlias = Qualification::ENTITY_NAME;
        $demEntity = $demPackage['entity']['Qualification'];
        $this->_toolDem->createEntity($entityAlias, $demEntity);
    }


}