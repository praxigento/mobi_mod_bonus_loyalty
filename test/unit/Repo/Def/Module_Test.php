<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Repo\Def;

use Praxigento\BonusLoyalty\Config as Cfg;

include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Module_UnitTest extends \Praxigento\Core\Test\BaseCase\Mockery
{
    /** @var  \Mockery\MockInterface */
    private $mConn;
    /** @var  \Mockery\MockInterface */
    private $mDba;
    /** @var  \Mockery\MockInterface */
    private $mRepoGeneric;
    /** @var  \Mockery\MockInterface */
    private $mRepoBonusBase;
    /** @var  \Mockery\MockInterface */
    private $mToolPeriod;
    /** @var  Module */
    private $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Test is deprecated after M1 & M2 merge is done.');
        $this->mConn = $this->_mockDba();
        $this->mDba = $this->_mockResourceConnection($this->mConn);
        $this->mRepoGeneric = $this->_mockRepoGeneric($this->mDba);
        $this->mRepoBonusBase = $this->_mock(\Praxigento\BonusBase\Repo\IModule::class);
        $this->mToolPeriod = $this->_mock(\Praxigento\Core\Tool\IPeriod::class);
        $this->repo = new Module(
            $this->mRepoGeneric,
            $this->mRepoBonusBase,
            $this->mToolPeriod
        );
    }

    public function test_getBonusPercents()
    {
        /** === Test Data === */
        $CALC_TYPE_ID = 2;
        $PERCENT = 'percent';
        /** === Setup Mocks === */
        // $calcTypeId = $this->_repoBonusBase->getTypeCalcIdByCode(Cfg::CODE_TYPE_CALC_BONUS);
        $this->mRepoBonusBase
            ->shouldReceive('getTypeCalcIdByCode')->once()
            ->andReturn($CALC_TYPE_ID);
        // $result = $this->_repoBonusBase->getConfigGenerationsPercents($calcTypeId);
        $this->mRepoBonusBase
            ->shouldReceive('getConfigGenerationsPercents')->once()
            ->with($CALC_TYPE_ID)
            ->andReturn($PERCENT);
        /** === Call and asserts  === */
        $resp = $this->repo->getBonusPercents();
        $this->assertEquals($PERCENT, $resp);
    }


    public function test_getCompressedTreeWithQualifications()
    {
        /** === Test Data === */
        $CALC_ID = 2;
        $RESULT = 'result';
        /** === Setup Mocks === */
        // $tblCompress = $this->_getTableName(Compress::ENTITY_NAME);
        $this->mDba
            ->shouldReceive('getTableName');
        // $query = $conn->select();
        $mQuery = $this->_mockDbSelect();
        $this->mConn
            ->shouldReceive('select')->once()
            ->andReturn($mQuery);
        //
        $mQuery->shouldReceive('from');
        $mQuery->shouldReceive('joinLeft');
        $mQuery->shouldReceive('where');
        // $result = $conn->fetchAll($query);
        $this->mConn
            ->shouldReceive('fetchAll')->once()
            ->andReturn($RESULT);
        /** === Call and asserts  === */
        $resp = $this->repo->getCompressedTreeWithQualifications($CALC_ID);
        $this->assertEquals($RESULT, $resp);
    }

    public function test_getConfigParams()
    {
        /** === Test Data === */
        $RESULT = 'result';
        /** === Setup Mocks === */
        // $result = $this->_repoBasic->getEntities(CfgParam::ENTITY_NAME, null, null, $order);
        $this->mRepoGeneric
            ->shouldReceive('getEntities')->once()
            ->andReturn($RESULT);
        /** === Call and asserts  === */
        $resp = $this->repo->getConfigParams();
        $this->assertEquals($RESULT, $resp);
    }

    public function test_getLatestCalcForPeriod()
    {
        /** === Test Data === */
        $CALC_TYPE_ID = 4;
        $DS_BEGIN = 'begin';
        $DS_END = 'end';
        $RESULT = 'result';
        /** === Setup Mocks === */
        // $result = $this->_repoBonusBase->getCalcsForPeriod($calcTypeId, $dsBegin, $dsEnd, $shouldGetLatestCalc);
        $this->mRepoBonusBase
            ->shouldReceive('getCalcsForPeriod')->once()
            ->with($CALC_TYPE_ID, $DS_BEGIN, $DS_END, true)
            ->andReturn($RESULT);
        /** === Call and asserts  === */
        $resp = $this->repo->getLatestCalcForPeriod($CALC_TYPE_ID, $DS_BEGIN, $DS_END);
        $this->assertEquals($RESULT, $resp);
    }


    public function test_getQualificationData()
    {
        /** === Test Data === */
        $DS_FROM = 'from';
        $DS_TO = 'to';
        $CUST_ID = 16;
        $SUMMARY = 32;
        $ITEMS = [
            [Cfg::E_SALE_ORDER_A_CUSTOMER_ID => $CUST_ID, 'summary' => $SUMMARY]
        ];
        $TS_FROM = 'ts from';
        $TS_TO = 'ts to';
        /** === Setup Mocks === */
        // $tsFrom = $this->_toolPeriod->getTimestampFrom($dsFrom);
        $this->mToolPeriod
            ->shouldReceive('getTimestampFrom')->once()
            ->andReturn($TS_FROM);
        // $tsTo = $this->_toolPeriod->getTimestampTo($dsTo);
        $this->mToolPeriod
            ->shouldReceive('getTimestampTo')->once()
            ->andReturn($TS_TO);
        // $tblCompress = $this->_getTableName(Compress::ENTITY_NAME);
        $this->mDba
            ->shouldReceive('getTableName');
        // $query = $conn->select();
        $mQuery = $this->_mockDbSelect();
        $this->mConn
            ->shouldReceive('select')->once()
            ->andReturn($mQuery);
        //
        $mQuery->shouldReceive('from');
        $mQuery->shouldReceive('joinLeft');
        $mQuery->shouldReceive('where');
        $mQuery->shouldReceive('group');
        // $conn->quote(...)
        $this->mConn->shouldReceive('quote');
        // $result = $conn->fetchAll($query);
        $this->mConn
            ->shouldReceive('fetchAll')->once()
            ->andReturn($ITEMS);
        /** === Call and asserts  === */
        $resp = $this->repo->getQualificationData($DS_FROM, $DS_TO);
        $this->assertTrue(is_array($resp));
        $this->assertEquals($SUMMARY, $resp[$CUST_ID]);
    }

    public function test_getSalesOrdersForPeriod()
    {
        /** === Test Data === */
        $DS_FROM = 'from';
        $DS_TO = 'to';
        $ITEMS = 'result';
        $TS_FROM = 'ts from';
        $TS_TO = 'ts to';
        /** === Setup Mocks === */
        // $tsFrom = $this->_toolPeriod->getTimestampFrom($dsFrom);
        $this->mToolPeriod
            ->shouldReceive('getTimestampFrom')->once()
            ->andReturn($TS_FROM);
        // $tsTo = $this->_toolPeriod->getTimestampTo($dsTo);
        $this->mToolPeriod
            ->shouldReceive('getTimestampTo')->once()
            ->andReturn($TS_TO);
        // $tblCompress = $this->_getTableName(Compress::ENTITY_NAME);
        $this->mDba
            ->shouldReceive('getTableName');
        // $query = $conn->select();
        $mQuery = $this->_mockDbSelect();
        $this->mConn
            ->shouldReceive('select')->once()
            ->andReturn($mQuery);
        //
        $mQuery->shouldReceive('from');
        $mQuery->shouldReceive('joinLeft');
        $mQuery->shouldReceive('where');
        // $conn->quote(...)
        $this->mConn->shouldReceive('quote');
        // $result = $conn->fetchAll($query);
        $this->mConn
            ->shouldReceive('fetchAll')->once()
            ->andReturn($ITEMS);
        /** === Call and asserts  === */
        $resp = $this->repo->getSalesOrdersForPeriod($DS_FROM, $DS_TO);
        $this->assertEquals($ITEMS, $resp);
    }

    public function test_getTypeCalcIdByCode()
    {
        /** === Test Data === */
        $TYPE_CODE = 'code';
        $RESULT = 'result';
        /** === Setup Mocks === */
        // $result = $this->_repoBonusBase->getTypeCalcIdByCode($calcTypeCode);
        $this->mRepoBonusBase
            ->shouldReceive('getTypeCalcIdByCode')->once()
            ->with($TYPE_CODE)
            ->andReturn($RESULT);
        /** === Call and asserts  === */
        $resp = $this->repo->getTypeCalcIdByCode($TYPE_CODE);
        $this->assertEquals($RESULT, $resp);
    }

    public function test_saveBonus_commit()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
        $this->mRepoGeneric
            ->shouldReceive('addEntity')->once();
        // $conn->commit();
        $this->mConn
            ->shouldReceive('commit')->once();
        /** === Call and asserts  === */
        $this->repo->saveBonus($UPDATES);
    }

    /**
     * @expectedException \Exception
     */
    public function test_saveBonus_rollback()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
        $this->mRepoGeneric
            ->shouldReceive('addEntity')->once()
            ->andThrow(new \Exception());
        // $conn->rollback();
        $this->mConn
            ->shouldReceive('rollback')->once();
        /** === Call and asserts  === */
        $this->repo->saveBonus($UPDATES);
    }

    public function test_saveLogSaleOrders_commit()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBonusBase->addLogSaleOrder($transId, $saleId);
        $this->mRepoBonusBase
            ->shouldReceive('addLogSaleOrder')->once();
        // $conn->commit();
        $this->mConn
            ->shouldReceive('commit')->once();
        /** === Call and asserts  === */
        $this->repo->saveLogSaleOrders($UPDATES);
    }

    /**
     * @expectedException \Exception
     */
    public function test_saveLogSaleOrders_rollback()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBonusBase->addLogSaleOrder($transId, $saleId);
        $this->mRepoBonusBase
            ->shouldReceive('addLogSaleOrder')->once()
            ->andThrow(new \Exception());
        // $conn->rollback();
        $this->mConn
            ->shouldReceive('rollback')->once();
        /** === Call and asserts  === */
        $this->repo->saveLogSaleOrders($UPDATES);
    }

    public function test_saveQualificationParams_commit()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
        $this->mRepoGeneric
            ->shouldReceive('addEntity')->once();
        // $conn->commit();
        $this->mConn
            ->shouldReceive('commit')->once();
        /** === Call and asserts  === */
        $this->repo->saveQualificationParams($UPDATES);
    }

    /**
     * @expectedException \Exception
     */
    public function test_saveQualificationParams_rollback()
    {
        /** === Test Data === */
        $UPDATES = [[]];
        /** === Setup Mocks === */
        // $conn->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
        $this->mRepoGeneric
            ->shouldReceive('addEntity')->once()
            ->andThrow(new \Exception());
        // $conn->rollback();
        $this->mConn
            ->shouldReceive('rollback')->once();
        /** === Call and asserts  === */
        $this->repo->saveQualificationParams($UPDATES);
    }

}