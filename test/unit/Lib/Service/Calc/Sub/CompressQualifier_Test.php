<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc\Sub;

use Praxigento\Pv\Data\Entity\Sale as PvSale;

include_once(__DIR__ . '/../../../../phpunit_bootstrap.php');

class CompressQualifier_UnitTest extends \Praxigento\Core\Lib\Test\BaseMockeryCase {

    /** @var  CompressQualifier */
    private $sub;

    protected function setUp() {
        parent::setUp();
        $this->sub = new CompressQualifier();
    }

    public function test_isQualified() {
        /** === Test Data === */
        $DATA = [ CompressQualifier::AS_HAS_ORDERS => 1 ];
        /** === Setup Mocks === */

        /** === Call and asserts  === */
        $resp = $this->sub->isQualified($DATA);
        $this->assertTrue($resp);
    }

}