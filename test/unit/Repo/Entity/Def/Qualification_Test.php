<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Repo\Dao;

include_once(__DIR__ . '/../../../phpunit_bootstrap.php');

class Qualification_UnitTest
    extends \Praxigento\Core\Test\BaseCase\Repo\Entity
{
    /** @var  Qualification */
    private $obj;

    protected function setUp()
    {
        parent::setUp();
        /* create object to test */
        $this->obj = new Qualification(
            $this->mResource,
            $this->mRepoGeneric
        );
    }

    public function test_constructor()
    {
        /* === Call and asserts  === */
        $this->assertInstanceOf(\Praxigento\BonusLoyalty\Repo\Dao\Qualification::class, $this->obj);
    }
}