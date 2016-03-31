<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc\Sub;

use Flancer32\Lib\DataObject;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\Downline\Lib\Entity\Snap;
use Praxigento\Pv\Lib\Entity\Sale as PvSale;

include_once(__DIR__ . '/../../../../phpunit_bootstrap.php');

class Bonus_UnitTest extends \Praxigento\Core\Lib\Test\BaseMockeryCase {
    /** @var  \Mockery\MockInterface */
    private $mCallDownlineSnap;
    /** @var  \Mockery\MockInterface */
    private $mRankQualifier;
    /** @var  \Mockery\MockInterface */
    private $mToolDownlineTree;
    /** @var  \Mockery\MockInterface */
    private $mToolFormat;
    /** @var  Bonus */
    private $sub;

    protected function setUp() {
        parent::setUp();
        $this->mCallDownlineSnap = $this->_mock(\Praxigento\Downline\Lib\Service\ISnap::class);
        $this->mToolFormat = $this->_mock(\Praxigento\Core\Lib\Tool\Format::class);
        $this->mToolDownlineTree = $this->_mock(\Praxigento\Downline\Lib\Tool\ITree::class);
        $this->mRankQualifier = $this->_mock(Bonus\RankQualifier::class);
        $this->sub = new Bonus(
            $this->mCallDownlineSnap,
            $this->mToolFormat,
            $this->mToolDownlineTree,
            $this->mRankQualifier
        );
    }

    public function test_calc() {
        /** === Test Data === */
        $CUST_1 = 1;
        $PARENT_1 = 101;
        $ORDR_1 = 10;
        $PV_1 = 100;
        $RANK_1 = 2;
        $PERCENT_1 = 0.01;
        $BONUS_1 = 'bonus';
        $TREE = 'tree';
        $TREE_EXP = [
            $CUST_1 => [ Snap::ATTR_PATH => 'path1' ]
        ];
        $MAP_RANKS = [ $PARENT_1 => $RANK_1 ];
        $ORDERS = [
            [
                Cfg::E_SALE_ORDER_A_CUSTOMER_ID => $CUST_1,
                PvSale::ATTR_SALE_ID            => $ORDR_1,
                PvSale::ATTR_TOTAL              => $PV_1
            ]
        ];
        $PARAMS = [

        ];
        $PERCENTS = [
            $RANK_1 => [ 1 => $PERCENT_1 ]
        ];
        $PARENTS = [ $PARENT_1 ];
        /** === Setup Mocks === */
        // $mapTreeExp = $this->_expandTree($tree);
        // $resp = $this->_callDownlineSnap->expandMinimal($req);
        $this->mCallDownlineSnap
            ->shouldReceive('expandMinimal')->once()
            ->andReturn(new DataObject([ 'SnapData' => $TREE_EXP ]));
        // $mapRankById = $this->_rankQualifier->qualifyCustomers($tree, $params);
        $this->mRankQualifier
            ->shouldReceive('qualifyCustomers')->once()
            ->andReturn($MAP_RANKS);
        // $parents = $this->_toolDownlineTree->getParentsFromPathReversed($path);
        $this->mToolDownlineTree
            ->shouldReceive('getParentsFromPathReversed')->once()
            ->andReturn($PARENTS);
        // $bonus = $this->_toolFormat->roundBonus($bonus);
        $this->mToolFormat
            ->shouldReceive('roundBonus')->once()
            ->with($PV_1 * $PERCENT_1)
            ->andReturn($BONUS_1);

        /** === Call and asserts  === */
        $resp = $this->sub->calc($TREE, $ORDERS, $PARAMS, $PERCENTS);
        $this->assertEquals($BONUS_1, $resp[$PARENT_1][$ORDR_1]);
    }

}