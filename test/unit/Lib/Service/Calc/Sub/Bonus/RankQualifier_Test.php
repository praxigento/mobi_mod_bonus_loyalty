<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc\Sub\Bonus;

use Praxigento\Bonus\Base\Lib\Entity\Compress;
use Praxigento\Bonus\Loyalty\Lib\Entity\Cfg\Param as CfgParam;
use Praxigento\Bonus\Loyalty\Lib\Entity\Qualification;

include_once(__DIR__ . '/../../../../../phpunit_bootstrap.php');

class RankQualifier_UnitTest extends \Praxigento\Core\Test\BaseMockeryCase {
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
                Compress::ATTR_CUSTOMER_ID => $CUST_ID,
                Qualification::ATTR_PV     => 10,
                Qualification::ATTR_GV     => 100,
                Qualification::ATTR_PSAA   => 2
            ]
        ];
        $PARAMS = [
            [
                CfgParam::ATTR_PV      => 5,
                CfgParam::ATTR_GV      => 50,
                CfgParam::ATTR_PSAA    => 2,
                CfgParam::ATTR_RANK_ID => $RANK_ID
            ]
        ];

        /** === Setup Mocks === */

        /** === Call and asserts  === */
        $resp = $this->obj->qualifyCustomers($CUSTOMERS, $PARAMS);
        $this->assertEquals($RANK_ID, $resp[$CUST_ID]);
    }

}