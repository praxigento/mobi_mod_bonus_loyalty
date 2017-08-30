<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub;

include_once(__DIR__ . '/../../../phpunit_bootstrap.php');

class CompressQualifier_UnitTest extends \Praxigento\Core\Test\BaseCase\Mockery {

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