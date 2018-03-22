<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Praxigento\Accounting\Repo\Data\Type\Operation as TypeOperation;
use Praxigento\BonusBase\Repo\Data\Type\Calc as TypeCalc;
use Praxigento\BonusLoyalty\Config as Cfg;

class InstallData extends \Praxigento\Core\App\Setup\Data\Base
{
    private function _addAccountingOperationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeOperation::ENTITY_NAME),
            [TypeOperation::ATTR_CODE, TypeOperation::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_OPER_BONUS_LOYALTY, 'Loyalty bonus.']
            ]
        );
    }

    private function _addBonusCalculationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeCalc::ENTITY_NAME),
            [TypeCalc::ATTR_CODE, TypeCalc::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_CALC_COMPRESSION, 'Compression for Loyalty bonus.'],
                [Cfg::CODE_TYPE_CALC_QUALIFICATION, 'Qualification parameters for Loyalty bonus.'],
                [Cfg::CODE_TYPE_CALC_BONUS, 'Loyalty bonus itself.']
            ]
        );
    }

    protected function _setup()
    {
        $this->_addAccountingOperationsTypes();
        $this->_addBonusCalculationsTypes();
    }
}