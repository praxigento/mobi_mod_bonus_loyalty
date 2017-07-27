<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Repo\Entity\Cfg\Def;

include_once(__DIR__ . '/../../../../phpunit_bootstrap.php');

class Param_UnitTest
    extends \Praxigento\Core\Test\BaseCase\Repo\Entity
{
    /** @var  Param */
    private $obj;

    protected function setUp()
    {
        parent::setUp();
        /* create object to test */
        $this->obj = new Param(
            $this->mResource,
            $this->mRepoGeneric
        );
    }

    public function test_constructor()
    {
        /* === Call and asserts  === */
        $this->assertInstanceOf(\Praxigento\BonusLoyalty\Repo\Entity\Cfg\Def\Param::class, $this->obj);
    }
}