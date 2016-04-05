<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc;

use Praxigento\Bonus\Base\Lib\Entity\Calculation;
use Praxigento\Bonus\Base\Lib\Entity\Period;
use Praxigento\Bonus\Base\Lib\Service\Compress\Request\QualifyByUserData as BonusBaseQualifyByUserDataRequest;
use Praxigento\Bonus\Base\Lib\Service\Period\Request\GetForDependentCalc as PeriodGetForDependentCalcRequest;
use Praxigento\Bonus\Base\Lib\Service\Period\Request\GetForPvBasedCalc as PeriodGetLatestForPvBasedCalcRequest;
use Praxigento\Bonus\Loyalty\Lib\Service\ICalc;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\Core\Lib\Service\Base\NeoCall as NeoCall;
use Praxigento\Downline\Lib\Service\Snap\Request\GetStateOnDate as DownlineSnapGetStateOnDateRequest;
use Praxigento\Pv\Data\Entity\Sale as PvSale;
use Praxigento\Wallet\Lib\Service\Operation\Request\AddToWalletActive as WalletOperationAddToWalletActiveRequest;

class Call extends NeoCall implements ICalc
{
    /** @var  \Praxigento\Bonus\Base\Lib\Service\ICompress */
    protected $_callBaseCompress;
    /** @var  \Praxigento\Bonus\Base\Lib\Service\IPeriod */
    protected $_callBasePeriod;
    /** @var  \Praxigento\Wallet\Lib\Service\IOperation */
    protected $_callWalletOperation;
    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;
    /** @var \Praxigento\Bonus\Loyalty\Lib\Repo\IModule */
    protected $_repoMod;
    /** @var Sub\Bonus */
    protected $_subBonus;
    /** @var Sub\Qualification */
    protected $_subQualification;
    /** @var  \Praxigento\Downline\Lib\Service\ISnap */
    protected $_callDownlineSnap;
    /** @var  \Praxigento\Core\Repo\ITransactionManager */
    protected $_manTrans;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Praxigento\Core\Repo\ITransactionManager $manTrans,
        \Praxigento\Bonus\Loyalty\Lib\Repo\IModule $repoMod,
        \Praxigento\Bonus\Base\Lib\Service\ICompress $callBaseCompress,
        \Praxigento\Bonus\Base\Lib\Service\IPeriod $callBasePeriod,
        \Praxigento\Downline\Lib\Service\ISnap $callDownlineSnap,
        \Praxigento\Wallet\Lib\Service\IOperation $callWalletOperation,
        Sub\Bonus $subBonus,
        Sub\Qualification $subQualification
    ) {
        $this->_logger = $logger;
        $this->_manTrans = $manTrans;
        $this->_repoMod = $repoMod;
        $this->_callBaseCompress = $callBaseCompress;
        $this->_callBasePeriod = $callBasePeriod;
        $this->_callDownlineSnap = $callDownlineSnap;
        $this->_callWalletOperation = $callWalletOperation;
        $this->_subBonus = $subBonus;
        $this->_subQualification = $subQualification;
    }

    /**
     * @param $updates
     *
     * @return \Praxigento\Wallet\Lib\Service\Operation\Response\AddToWalletActive
     */
    private function _createBonusOperation($updates)
    {
        $asCustId = 'asCid';
        $asAmount = 'asAmnt';
        $asRef = 'asRef';
        $transData = [];
        foreach ($updates as $custId => $sales) {
            foreach ($sales as $saleId => $amount) {
                $item = [$asCustId => $custId, $asAmount => $amount, $asRef => $saleId];
                $transData[] = $item;
            }
        }
        $req = new WalletOperationAddToWalletActiveRequest();
        $req->setAsCustomerId($asCustId);
        $req->setAsAmount($asAmount);
        $req->setAsRef($asRef);
        $req->setOperationTypeCode(Cfg::CODE_TYPE_OPER_BONUS_LOYALTY);
        $req->setTransData($transData);
        $result = $this->_callWalletOperation->addToWalletActive($req);
        return $result;
    }

    /**
     * Get Downline Tree snapshot on the $ds (datestamp). Result is an array [$customerId => [...], ...]
     *
     * @param $ds 'YYYYMMDD'
     *
     * @return array|null
     */
    private function _getDownlineSnapshot($ds)
    {
        $req = new DownlineSnapGetStateOnDateRequest();
        $req->setDatestamp($ds);
        $resp = $this->_callDownlineSnap->getStateOnDate($req);
        $result = $resp->getData();
        return $result;
    }

    /**
     * @param Request\Bonus $req
     *
     * @return Response\Bonus
     */
    public function bonus(Request\Bonus $req)
    {
        $result = new Response\Bonus();
        $datePerformed = $req->getDatePerformed();
        $dateApplied = $req->getDateApplied();
        $this->_logger->info("'Loyalty Bonus' calculation is started. Performed at: $datePerformed, applied at: $dateApplied.");
        $reqGetPeriod = new PeriodGetForDependentCalcRequest();
        $calcTypeBase = Cfg::CODE_TYPE_CALC_QUALIFICATION;
        $calcType = Cfg::CODE_TYPE_CALC_BONUS;
        $reqGetPeriod->setBaseCalcTypeCode($calcTypeBase);
        $reqGetPeriod->setDependentCalcTypeCode($calcType);
        $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $trans = $this->_manTrans->transactionBegin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend[Calculation::ATTR_ID];
                $dsBegin = $periodDataDepend[Period::ATTR_DSTAMP_BEGIN];
                $dsEnd = $periodDataDepend[Period::ATTR_DSTAMP_END];
                /* collect data to process bonus */
                $calcTypeIdCompress = $this->_repoMod->getTypeCalcIdByCode(Cfg::CODE_TYPE_CALC_COMPRESSION);
                $calcDataCompress = $this->_repoMod->getLatestCalcForPeriod($calcTypeIdCompress, $dsBegin, $dsEnd);
                $calcIdCompress = $calcDataCompress[Calculation::ATTR_ID];
                $params = $this->_repoMod->getConfigParams();
                $percents = $this->_repoMod->getBonusPercents();
                $treeCompressed = $this->_repoMod->getCompressedTreeWithQualifications($calcIdCompress);
                $orders = $this->_repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
                /* calculate bonus */
                $updates = $this->_subBonus->calc($treeCompressed, $orders, $params, $percents);
                /* create new operation with bonus transactions and save sales log */
                $respAdd = $this->_createBonusOperation($updates);
                $transLog = $respAdd->getTransactionsIds();
                $this->_repoMod->saveLogSaleOrders($transLog);
                /* mark calculation as completed and finalize bonus */
                $this->_repoMod->updateCalcSetComplete($calcIdDepend);
                $this->_manTrans->transactionCommit($trans);
                $result->setPeriodId($periodDataDepend[Period::ATTR_ID]);
                $result->setCalcId($calcIdDepend);
                $result->setAsSucceed();
            } finally {
                $this->_manTrans->transactionClose($trans);
            }
        }
        $this->_logger->info("'Loyalty Bonus' calculation is complete.");
        return $result;
    }

    public function compress(Request\Compress $req)
    {
        $result = new Response\Compress();
        $calcTypeCode = Cfg::CODE_TYPE_CALC_COMPRESSION;
        $this->_logger->info("'Loyalty Compression' calculation is started.");
        $reqGetLatest = new PeriodGetLatestForPvBasedCalcRequest();
        $reqGetLatest->setCalcTypeCode($calcTypeCode);
        $respGetLatest = $this->_callBasePeriod->getForPvBasedCalc($reqGetLatest);
        if ($respGetLatest->isSucceed()) {
            $trans = $this->_manTrans->transactionBegin();
            try {
                /* get tree snapshot and orders data */
                $periodData = $respGetLatest->getPeriodData();
                $dsBegin = $periodData[Period::ATTR_DSTAMP_BEGIN];
                $dsEnd = $periodData[Period::ATTR_DSTAMP_END];
                $tree = $this->_getDownlineSnapshot($dsEnd);
                $orders = $this->_repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
                /* match orders to customers  */
                foreach ($orders as $order) {
                    $custId = $order[Cfg::E_SALE_ORDER_A_CUSTOMER_ID];
                    $tree[$custId][Sub\CompressQualifier::AS_HAS_ORDERS] = true;
                }
                /* call to compression operation from bonus base module */
                $reqCompress = new BonusBaseQualifyByUserDataRequest();
                $calcData = $respGetLatest->getCalcData();
                $calcId = $calcData[Calculation::ATTR_ID];
                $reqCompress->setCalcId($calcId);
                $reqCompress->setFlatTree($tree);
                $reqCompress->setSkipTreeExpand(true);
                $reqCompress->setQualifier(new Sub\CompressQualifier());
                $respCompress = $this->_callBaseCompress->qualifyByUserData($reqCompress);
                if ($respCompress->isSucceed()) {
                    $this->_repoMod->updateCalcSetComplete($calcId);
                    $this->_manTrans->transactionCommit($trans);
                    $result->setPeriodId($periodData[Period::ATTR_ID]);
                    $result->setCalcId($calcId);
                    $result->setAsSucceed();
                }
            } finally {
                $this->_manTrans->transactionClose($trans);
            }
        }
        $this->_logger->info("'Loyalty Compression' calculation is complete.");
        return $result;
    }

    public function qualification(Request\Qualification $req)
    {
        $result = new Response\Qualification();
        $datePerformed = $req->getDatePerformed();
        $dateApplied = $req->getDateApplied();
        $gvMaxLevels = $req->getGvMaxLevels();
        $psaaLevel = $req->getPsaaLevel();
        $this->_logger->info("'Qualification for Loyalty' calculation is started. Performed at: $datePerformed, applied at: $dateApplied.");
        $reqGetPeriod = new PeriodGetForDependentCalcRequest();
        $calcTypeBase = Cfg::CODE_TYPE_CALC_COMPRESSION;
        $calcType = Cfg::CODE_TYPE_CALC_QUALIFICATION;
        $reqGetPeriod->setBaseCalcTypeCode($calcTypeBase);
        $reqGetPeriod->setDependentCalcTypeCode($calcType);
        $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $trans = $this->_manTrans->transactionBegin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend[Calculation::ATTR_ID];
                $calcDataBase = $respGetPeriod->getBaseCalcData();
                $dsBegin = $periodDataDepend[Period::ATTR_DSTAMP_BEGIN];
                $dsEnd = $periodDataDepend[Period::ATTR_DSTAMP_END];
                $calcIdBase = $calcDataBase[Calculation::ATTR_ID];
                $tree = $this->_repoMod->getCompressedTree($calcIdBase);
                $qualData = $this->_repoMod->getQualificationData($dsBegin, $dsEnd);
                $updates = $this->_subQualification->calcParams($tree, $qualData, $gvMaxLevels, $psaaLevel);
                $this->_repoMod->saveQualificationParams($updates);
                $this->_repoMod->updateCalcSetComplete($calcIdDepend);
                $this->_manTrans->transactionCommit($trans);
                $result->setPeriodId($periodDataDepend[Period::ATTR_ID]);
                $result->setCalcId($calcIdDepend);
                $result->setAsSucceed();
            } finally {
                $this->_manTrans->transactionClose($trans);
            }
        }
        $this->_logger->info("'Qualification for Loyalty' calculation is complete.");
        return $result;
    }
}