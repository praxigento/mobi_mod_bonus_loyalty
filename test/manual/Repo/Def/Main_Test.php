<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Repo\Def;



include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Main_ManualTest extends \Praxigento\Core\Test\BaseCase\Mockery {

    public function test_construct() {
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\BonusLoyalty\Repo\IModule */
        $repo = $obm->get(\Praxigento\BonusLoyalty\Repo\IModule::class);
        $this->assertTrue($repo instanceof \Praxigento\BonusLoyalty\Repo\Def\Module);
    }

    public function test_getQualificationData() {
        $FROM = '20160101';
        $TO = '20161231';
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\BonusLoyalty\Repo\IModule */
        $repo = $obm->get(\Praxigento\BonusLoyalty\Repo\IModule::class);
        $data = $repo->getQualificationData($FROM, $TO);
        $this->assertTrue(is_array($data));
    }

    public function test_getSalesOrdersForCompression() {
        $FROM = '20160101';
        $TO = '20161231';
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\BonusLoyalty\Repo\IModule */
        $repo = $obm->get(\Praxigento\BonusLoyalty\Repo\IModule::class);
        $data = $repo->getSalesOrdersForPeriod($FROM, $TO);
        $this->assertTrue(is_array($data));
    }
}