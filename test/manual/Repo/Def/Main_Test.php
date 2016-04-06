<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Bonus\Loyalty\Lib\Repo\Def;



include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Main_ManualTest extends \Praxigento\Core\Lib\Test\BaseTestCase {

    public function test_construct() {
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\Bonus\Loyalty\Lib\Repo\IModule */
        $repo = $obm->get(\Praxigento\Bonus\Loyalty\Lib\Repo\IModule::class);
        $this->assertTrue($repo instanceof \Praxigento\Bonus\Loyalty\Lib\Repo\Def\Module);
    }

    public function test_getQualificationData() {
        $FROM = '20160101';
        $TO = '20161231';
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\Bonus\Loyalty\Lib\Repo\IModule */
        $repo = $obm->get(\Praxigento\Bonus\Loyalty\Lib\Repo\IModule::class);
        $data = $repo->getQualificationData($FROM, $TO);
        $this->assertTrue(is_array($data));
    }

    public function test_getSalesOrdersForCompression() {
        $FROM = '20160101';
        $TO = '20161231';
        $obm = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var  $repo \Praxigento\Bonus\Loyalty\Lib\Repo\IModule */
        $repo = $obm->get(\Praxigento\Bonus\Loyalty\Lib\Repo\IModule::class);
        $data = $repo->getSalesOrdersForPeriod($FROM, $TO);
        $this->assertTrue(is_array($data));
    }
}