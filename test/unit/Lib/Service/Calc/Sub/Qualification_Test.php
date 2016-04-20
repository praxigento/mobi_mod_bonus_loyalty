<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc\Sub;

use Flancer32\Lib\DataObject;

include_once(__DIR__ . '/../../../../phpunit_bootstrap.php');

class Qualification_UnitTest extends \Praxigento\Core\Test\BaseMockeryCase
{
    /** @var  \Mockery\MockInterface */
    protected $mCallDownlineMap;
    /** @var  \Mockery\MockInterface */
    protected $mCallDownlineSnap;
    /** @var  \Mockery\MockInterface */
    protected $mToolDownlineTree;
    /** @var  Qualification */
    private $sub;

    protected function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Test is deprecated after M1 & M2 merge is done.');
        $this->mCallDownlineMap = $this->_mock(\Praxigento\Downline\Lib\Service\IMap::class);
        $this->mCallDownlineSnap = $this->_mock(\Praxigento\Downline\Lib\Service\ISnap::class);
        $this->mToolDownlineTree = $this->_mock(\Praxigento\Downline\Lib\Tool\ITree::class);
        $this->sub = new Qualification(
            $this->mCallDownlineMap,
            $this->mCallDownlineSnap,
            $this->mToolDownlineTree
        );
    }

    /**
     * Tree: 1 => 2 => 3 => 4
     */
    public function test_calcParams()
    {
        /** === Test Data === */
        $TREE = 'tree';
        $CUST_1 = 1;
        $CUST_2 = 2;
        $CUST_3 = 3;
        $CUST_4 = 4;
        $Q_DATA = [
            $CUST_1 => 400,
            $CUST_2 => 300,
            $CUST_3 => 200,
            $CUST_4 => 100
        ];
        $MAX_LEVELS = 2;
        $PSAA_LEVEL = 120;
        $TREE_EXP = [];
        $MAP_BY_DEPTH = [
            3 => [$CUST_4],
            2 => [$CUST_3],
            1 => [$CUST_2],
            0 => [$CUST_1]
        ];
        $MAP_BY_TEAMS = [
            $CUST_1 => [$CUST_2],
            $CUST_2 => [$CUST_3],
            $CUST_3 => [$CUST_4]
        ];
        $MAP_BY_ID = [];
        /** === Setup Mocks === */
        // $treeExpanded = $this->_expandTree($tree);
        // $resp = $this->_callDownlineSnap->expandMinimal($req);
        $this->mCallDownlineSnap
            ->shouldReceive('expandMinimal')->once()
            ->andReturn(new DataObject(['SnapData' => $TREE_EXP]));
        // $mapByDepth = $this->_mapByTreeDepthDesc($treeExpanded);
        // $resp = $this->_callDownlineMap->treeByDepth($req);
        $this->mCallDownlineMap
            ->shouldReceive('treeByDepth')->once()
            ->andReturn(new DataObject(['Mapped' => $MAP_BY_DEPTH]));
        // $mapTeams = $this->_mapByTeams($tree);
        // $resp = $this->_callDownlineMap->treeByTeams($req);
        $this->mCallDownlineMap
            ->shouldReceive('treeByTeams')->once()
            ->andReturn(new DataObject(['Mapped' => $MAP_BY_TEAMS]));
        // $mapById = $this->_mapById($tree);
        // $resp = $this->_callDownlineMap->byId($req);
        $this->mCallDownlineMap
            ->shouldReceive('byId')->once()
            ->andReturn(new DataObject(['Mapped' => $MAP_BY_ID]));
        // $parents = $this->_toolDownlineTree->getParentsFromPathReversed($path);
        $this->mToolDownlineTree
            ->shouldReceive('getParentsFromPathReversed')->once()
            ->andReturn([$CUST_3, $CUST_2, $CUST_1]);
        $this->mToolDownlineTree
            ->shouldReceive('getParentsFromPathReversed')->once()
            ->andReturn([$CUST_2, $CUST_1]);
        $this->mToolDownlineTree
            ->shouldReceive('getParentsFromPathReversed')->once()
            ->andReturn([$CUST_1]);
        $this->mToolDownlineTree
            ->shouldReceive('getParentsFromPathReversed')->once()
            ->andReturn([]);

        /** === Call and asserts  === */
        $resp = $this->sub->calcParams($TREE, $Q_DATA, $MAX_LEVELS, $PSAA_LEVEL);
        $this->assertTrue(is_array($resp));
    }

}