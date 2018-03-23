<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Service\Calc\Sub\Bonus;

use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Repo\Data\Cfg\Param as CfgParam;
use Praxigento\BonusLoyalty\Repo\Data\Qualification;

include_once(__DIR__ . '/../../../../phpunit_bootstrap.php');

class RankQualifier_UnitTest extends \Praxigento\Core\Test\BaseCase\Mockery {
    /** @var  RankQualifier */
    private $obj;

    protected function setUp() {
        parent::setUp();
        $this->obj = new RankQualifier();
    }

    public function test_qualifyCustomers() {
        /** === Test Data === */
        $CUST_ID = 1;
        $RANK_ID = 5;
        $CUSTOMERS = [
            [
                Compress::A_CUSTOMER_ID => $CUST_ID,
                Qualification::A_PV     => 10,
                Qualification::A_GV     => 100,
                Qualification::A_PSAA   => 2
            ]
        ];
        $PARAMS = [
            [
                CfgParam::A_PV      => 5,
                CfgParam::A_GV      => 50,
                CfgParam::A_PSAA    => 2,
                CfgParam::A_RANK_ID => $RANK_ID
            ]
        ];

        /** === Setup Mocks === */

        /** === Call and asserts  === */
        $resp = $this->obj->qualifyCustomers($CUSTOMERS, $PARAMS);
        $this->assertEquals($RANK_ID, $resp[$CUST_ID]);
    }

}