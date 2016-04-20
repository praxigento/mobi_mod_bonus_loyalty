<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc;

use Flancer32\Lib\DataObject;
use Praxigento\Bonus\Base\Lib\Entity\Calculation;
use Praxigento\Bonus\Base\Lib\Entity\Period;
use Praxigento\BonusLoyalty\Config as Cfg;

include_once(__DIR__ . '/../../../phpunit_bootstrap.php');

class Call_UnitTest extends \Praxigento\Core\Lib\Test\BaseMockeryCase
{
    /** @var  Call */
    private $call;
    /** @var  \Mockery\MockInterface */
    private $mCallBaseCompress;
    /** @var  \Mockery\MockInterface */
    private $mCallBasePeriod;
    /** @var  \Mockery\MockInterface */
    private $mCallDownlineSnap;
    /** @var  \Mockery\MockInterface */
    private $mCallWalletOperation;
    /** @var  \Mockery\MockInterface */
    private $mRepoMod;
    /** @var  \Mockery\MockInterface */
    private $mSubBonus;
    /** @var  \Mockery\MockInterface */
    private $mSubQual;

    protected function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Test is deprecated after M1 & M2 merge is done.');
        $this->mConn = $this->_mockDba();
        $this->mDba = $this->_mockRsrcConnOld($this->mConn);
        $this->mRepoBasic = $this->_mockRepoBasic($this->mDba);
        $this->mRepoMod = $this->_mockRepoMod(
            \Praxigento\Bonus\Loyalty\Lib\Repo\IModule::class,
            $this->mRepoBasic
        );
        $this->mLogger = $this->_mockLogger();
        $this->mCallBaseCompress = $this->_mock(\Praxigento\Bonus\Base\Lib\Service\ICompress::class);
        $this->mCallBasePeriod = $this->_mock(\Praxigento\Bonus\Base\Lib\Service\IPeriod::class);
        $this->mCallDownlineSnap = $this->_mock(\Praxigento\Downline\Lib\Service\ISnap::class);
        $this->mCallWalletOperation = $this->_mock(\Praxigento\Wallet\Lib\Service\IOperation::class);
        $this->mSubBonus = $this->_mock(Sub\Bonus::class);
        $this->mSubQual = $this->_mock(Sub\Qualification::class);
        $this->call = new Call(
            $this->mLogger,
            $this->mRepoMod,
            $this->mCallBaseCompress,
            $this->mCallBasePeriod,
            $this->mCallDownlineSnap,
            $this->mCallWalletOperation,
            $this->mSubBonus,
            $this->mSubQual
        );
    }

    /**
     * @expectedException \Exception
     */
    public function test_bonus_rollback()
    {
        /** === Test Data === */
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';

        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForDependentCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForDependentCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $calcTypeIdCompress = $this->_repoMod->getTypeCalcIdByCode(Cfg::CODE_TYPE_CALC_COMPRESSION);
        $this->mRepoMod
            ->shouldReceive('getTypeCalcIdByCode')
            ->andThrow(new \Exception());
        // $conn->commit();
        $this->mConn->shouldReceive('rollback')->once();

        /** === Call and asserts  === */
        $req = new Request\Bonus();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $resp = $this->call->bonus($req);
        $this->assertFalse($resp->isSucceed());
    }

    public function test_bonus_success()
    {
        /** === Test Data === */
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';
        $DS_BEGIN = 'begin';
        $DS_END = 'end';
        $CALC_TYPE_ID_COMPRESS = 4;
        $CALC_ID_COMPRESS = 8;
        $CALC_DATA_COMPRESS = [Calculation::ATTR_ID => $CALC_ID_COMPRESS];
        $CONFI_PARAMS = [];
        $PERCENTS = 'percents';
        $TREE_COMPRESS = [];
        $ORDERS = 'orders';
        $UPDATES = ['custId' => ['rankId' => 'amount']];
        $TRANS_LOG = [];
        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForDependentCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForDependentCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $dsBegin = $periodDataDepend[Period::ATTR_DSTAMP_BEGIN];
        // $dsEnd = $periodDataDepend[Period::ATTR_DSTAMP_END];
        $mRespGetPeriod->setDependentPeriodData([
            Period::ATTR_DSTAMP_BEGIN => $DS_BEGIN,
            Period::ATTR_DSTAMP_END => $DS_END
        ]);
        // $calcTypeIdCompress = $this->_repoMod->getTypeCalcIdByCode(Cfg::CODE_TYPE_CALC_COMPRESSION);
        $this->mRepoMod
            ->shouldReceive('getTypeCalcIdByCode')
            ->andReturn($CALC_TYPE_ID_COMPRESS);
        // $calcDataCompress = $this->_repoMod->getLatestCalcForPeriod($calcTypeIdCompress, $dsBegin, $dsEnd);
        $this->mRepoMod
            ->shouldReceive('getLatestCalcForPeriod')
            ->with($CALC_TYPE_ID_COMPRESS, $DS_BEGIN, $DS_END)
            ->andReturn($CALC_DATA_COMPRESS);
        // $params = $this->_repoMod->getConfigParams();
        $this->mRepoMod
            ->shouldReceive('getConfigParams')
            ->andReturn($CONFI_PARAMS);
        // $percents = $this->_repoMod->getBonusPercents();
        $this->mRepoMod
            ->shouldReceive('getBonusPercents')
            ->andReturn($PERCENTS);
        // $treeCompressed = $this->_repoMod->getCompressedTreeWithQualifications($calcIdCompress);
        $this->mRepoMod
            ->shouldReceive('getCompressedTreeWithQualifications')->once()
            ->with($CALC_ID_COMPRESS)
            ->andReturn($TREE_COMPRESS);
        // $orders = $this->_repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
        $this->mRepoMod
            ->shouldReceive('getSalesOrdersForPeriod')
            ->with($DS_BEGIN, $DS_END)
            ->andReturn($ORDERS);
        // $updates = $this->_subBonus->calc($treeCompressed, $pvTotal, $params);
        $this->mSubBonus
            ->shouldReceive('calc')
            ->andReturn($UPDATES);
        // $respAdd = $this->_createBonusOperation($updates);
        // $result = $this->_callWalletOperation->addToWalletActive($req);
        $mRespAddWallet = new \Praxigento\Wallet\Lib\Service\Operation\Response\AddToWalletActive();
        $this->mCallWalletOperation
            ->shouldReceive('addToWalletActive')
            ->andReturn($mRespAddWallet);
        // $transLog = $respAdd->getTransactionsIds();
        $mRespAddWallet->setTransactionsIds($TRANS_LOG);
        // $this->_repoMod->saveLogSaleOrders($transLog);
        $this->mRepoMod
            ->shouldReceive('saveLogSaleOrders')->once();
        // $this->_repoMod->updateCalcSetComplete($calcIdDepend);
        $this->mRepoMod
            ->shouldReceive('updateCalcSetComplete')->once();
        // $conn->commit();
        $this->mConn->shouldReceive('commit')->once();

        /** === Call and asserts  === */
        $req = new Request\Bonus();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $resp = $this->call->bonus($req);
        $this->assertTrue($resp->isSucceed());
    }

    /**
     * @expectedException \Exception
     */
    public function test_compress_rollback()
    {
        /** === Test Data === */
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';

        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetLatest = $this->_callBasePeriod->getForPvBasedCalc($reqGetLatest);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForPvBasedCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForPvBasedCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $tree = $this->_getDownlineSnapshot($dsEnd);
        // $resp = $this->_callDownlineSnap->getStateOnDate($req);
        $this->mCallDownlineSnap
            ->shouldReceive('getStateOnDate')->once()
            ->andThrow(new \Exception());

        $this->mRepoMod
            ->shouldReceive('getTypeCalcIdByCode')
            ->andThrow(new \Exception());
        // $conn->commit();
        $this->mConn->shouldReceive('rollback')->once();

        /** === Call and asserts  === */
        $req = new Request\Compress();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $resp = $this->call->compress($req);
        $this->assertFalse($resp->isSucceed());
    }

    public function test_compress_success()
    {
        /** === Test Data === */
        $CUST_ID = 1;
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';
        $DS_BEGIN = 'begin';
        $DS_END = 'end';
        $CALC_TYPE_ID_COMPRESS = 4;
        $CALC_ID_COMPRESS = 8;
        $CALC_DATA_COMPRESS = [Calculation::ATTR_ID => $CALC_ID_COMPRESS];
        $CONFI_PARAMS = [];
        $PERCENTS = 'percents';
        $TREE_COMPRESS = [];
        $ORDERS = [
            [Cfg::E_SALE_ORDER_A_CUSTOMER_ID => $CUST_ID]
        ];
        $UPDATES = ['custId' => ['rankId' => 'amount']];
        $TRANS_LOG = [];
        $DOWNLINE_SNAP = [$CUST_ID => []];
        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetLatest = $this->_callBasePeriod->getForPvBasedCalc($reqGetLatest);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForPvBasedCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForPvBasedCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $dsBegin = $periodDataDepend[Period::ATTR_DSTAMP_BEGIN];
        // $dsEnd = $periodDataDepend[Period::ATTR_DSTAMP_END];
        $mRespGetPeriod->setPeriodData([
            Period::ATTR_DSTAMP_BEGIN => $DS_BEGIN,
            Period::ATTR_DSTAMP_END => $DS_END
        ]);
        // $tree = $this->_getDownlineSnapshot($dsEnd);
        // $resp = $this->_callDownlineSnap->getStateOnDate($req);
        $this->mCallDownlineSnap
            ->shouldReceive('getStateOnDate')->once()
            ->andReturn(new DataObject($DOWNLINE_SNAP));
        // $orders = $this->_repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
        $this->mRepoMod
            ->shouldReceive('getSalesOrdersForPeriod')
            ->with($DS_BEGIN, $DS_END)
            ->andReturn($ORDERS);
        // $respCompress = $this->_callBaseCompress->qualifyByUserData($reqCompress);
        $mRespCompress = new \Praxigento\Bonus\Base\Lib\Service\Compress\Response\QualifyByUserData();
        $this->mCallBaseCompress
            ->shouldReceive('qualifyByUserData')->once()
            ->andReturn($mRespCompress);
        // if($respCompress->isSucceed()) {...}
        $mRespCompress->markSucceed();
        // $this->_repoMod->updateCalcSetComplete($calcIdDepend);
        $this->mRepoMod
            ->shouldReceive('updateCalcSetComplete')->once();
        // $conn->commit();
        $this->mConn->shouldReceive('commit')->once();

        /** === Call and asserts  === */
        $req = new Request\Compress();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $resp = $this->call->compress($req);
        $this->assertTrue($resp->isSucceed());
    }

    public function test_qualification_commit()
    {
        /** === Test Data === */
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';
        $GV_MAX_LEVELS = 3;
        $TREE_COMPRESS = [];
        $QUAL_DATA = [];
        $CFG_PARAMS = [];
        $UPDATES = [];
        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForDependentCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForDependentCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $tree = $this->_repoMod->getCompressedTree($calcIdBase);
        $this->mRepoMod
            ->shouldReceive('getCompressedTree')->once()
            ->andReturn($TREE_COMPRESS);
        // $qualData = $this->_repoMod->getQualificationData($dsBegin, $dsEnd);
        $this->mRepoMod
            ->shouldReceive('getQualificationData')->once()
            ->andReturn($QUAL_DATA);
        // $cfgParams = $this->_repoMod->getConfigParams();
        $this->mRepoMod
            ->shouldReceive('getConfigParams')->once()
            ->andReturn($CFG_PARAMS);
        // $updates = $this->_subQualification->calcParams($tree, $qualData, $cfgParams, $gvMaxLevels);
        $this->mSubQual
            ->shouldReceive('calcParams')->once()
            ->andReturn($UPDATES);
        // $this->_repoMod->saveQualificationParams($updates);
        $this->mRepoMod
            ->shouldReceive('saveQualificationParams')->once();
        // $this->_repoMod->updateCalcSetComplete($calcIdDepend);
        $this->mRepoMod
            ->shouldReceive('updateCalcSetComplete')->once();
        // $conn->commit();
        $this->mConn->shouldReceive('commit')->once();

        /** === Call and asserts  === */
        $req = new Request\Qualification();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $req->setGvMaxLevels($GV_MAX_LEVELS);
        $resp = $this->call->qualification($req);
        $this->assertTrue($resp->isSucceed());
    }

    /**
     * @expectedException \Exception
     */
    public function test_qualification_rollback()
    {
        /** === Test Data === */
        $DATE_PERFORMED = 'performed';
        $DATE_APPLIED = 'applied';
        $GV_MAX_LEVELS = 3;
        /** === Setup Mocks === */
        $this->mLogger->shouldReceive('info');
        // $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        $mRespGetPeriod = new \Praxigento\Bonus\Base\Lib\Service\Period\Response\GetForDependentCalc();
        $this->mCallBasePeriod
            ->shouldReceive('getForDependentCalc')->once()
            ->andReturn($mRespGetPeriod);
        // if($respGetPeriod->isSucceed()) {
        $mRespGetPeriod->markSucceed();
        // $this->_getConn()->beginTransaction();
        $this->mConn
            ->shouldReceive('beginTransaction')->once();
        // $tree = $this->_repoMod->getCompressedTree($calcIdBase);
        $this->mRepoMod
            ->shouldReceive('getCompressedTree')
            ->andThrow(new \Exception());
        // $conn->rollback();
        $this->mConn->shouldReceive('rollback')->once();

        /** === Call and asserts  === */
        $req = new Request\Qualification();
        $req->setDatePerformed($DATE_PERFORMED);
        $req->setDateApplied($DATE_APPLIED);
        $req->setGvMaxLevels($GV_MAX_LEVELS);
        $resp = $this->call->qualification($req);
        $this->assertFalse($resp->isSucceed());
    }

}