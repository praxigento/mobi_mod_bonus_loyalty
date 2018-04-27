<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Service\Calc;

use Praxigento\BonusBase\Service\Compress\Request\QualifyByUserData as BonusBaseQualifyByUserDataRequest;
use Praxigento\BonusBase\Service\Period\Request\GetForDependentCalc as PeriodGetForDependentCalcRequest;
use Praxigento\BonusBase\Service\Period\Request\GetForPvBasedCalc as PeriodGetLatestForPvBasedCalcRequest;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\Downline\Service\Snap\Request\GetStateOnDate as DownlineSnapGetStateOnDateRequest;
use Praxigento\Wallet\Service\Operation\Request\AddToWalletActive as WalletOperationAddToWalletActiveRequest;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Call
    implements \Praxigento\BonusLoyalty\Service\ICalc
{
    /** @var  \Praxigento\BonusBase\Service\ICompress */
    private $callBaseCompress;
    /** @var  \Praxigento\BonusBase\Service\IPeriod */
    private $callBasePeriod;
    /** @var  \Praxigento\Downline\Service\ISnap */
    private $callDownlineSnap;
    /** @var  \Praxigento\Wallet\Service\IOperation */
    private $callWalletOperation;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var  \Praxigento\Core\Api\App\Repo\Transaction\Manager */
    private $manTrans;
    /** @var  \Praxigento\BonusBase\Repo\Dao\Compress */
    private $repoBonusCompress;
    /** @var \Praxigento\BonusBase\Repo\Service\IModule */
    private $repoBonusService;
    /** @var \Praxigento\BonusBase\Repo\Dao\Type\Calc */
    private $repoBonusTypeCalc;
    /** @var \Praxigento\BonusLoyalty\Repo\IModule */
    private $repoMod;
    /** @var Sub\Bonus */
    private $subBonus;
    /** @var Sub\Qualification */
    private $subQualification;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\Core\Api\App\Repo\Transaction\Manager $manTrans,
        \Praxigento\BonusLoyalty\Repo\IModule $daoMod,
        \Praxigento\BonusBase\Repo\Service\IModule $daoBonusService,
        \Praxigento\BonusBase\Repo\Dao\Compress $daoBonusCompress,
        \Praxigento\BonusBase\Repo\Dao\Type\Calc $daoBonusTypeCalc,
        \Praxigento\BonusBase\Service\ICompress $callBaseCompress,
        \Praxigento\BonusBase\Service\IPeriod $callBasePeriod,
        \Praxigento\Downline\Service\ISnap $callDownlineSnap,
        \Praxigento\Wallet\Service\IOperation $callWalletOperation,
        Sub\Bonus $subBonus,
        Sub\Qualification $subQualification
    ) {
        $this->logger = $logger;
        $this->manTrans = $manTrans;
        $this->repoMod = $daoMod;
        $this->repoBonusService = $daoBonusService;
        $this->repoBonusCompress = $daoBonusCompress;
        $this->repoBonusTypeCalc = $daoBonusTypeCalc;
        $this->callBaseCompress = $callBaseCompress;
        $this->callBasePeriod = $callBasePeriod;
        $this->callDownlineSnap = $callDownlineSnap;
        $this->callWalletOperation = $callWalletOperation;
        $this->subBonus = $subBonus;
        $this->subQualification = $subQualification;
    }

    /**
     * @param $updates
     *
     * @return \Praxigento\Wallet\Service\Operation\Response\AddToWalletActive
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
        $result = $this->callWalletOperation->addToWalletActive($req);
        return $result;
    }

    /**
     * Get Downline Tree snapshot on the $ds (datestamp). Result is an array [$customerId => [...], ...]
     *
     * @param $dstamp 'YYYYMMDD'
     *
     * @return array|null
     */
    private function _getDownlineSnapshot($dstamp)
    {
        $req = new DownlineSnapGetStateOnDateRequest();
        $req->setDatestamp($dstamp);
        $resp = $this->callDownlineSnap->getStateOnDate($req);
        $result = $resp->get();
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
        $this->logger->info("'Loyalty Bonus' calculation is started. Performed at: $datePerformed, applied at: $dateApplied.");
        $reqGetPeriod = new PeriodGetForDependentCalcRequest();
        $calcTypeBase = Cfg::CODE_TYPE_CALC_QUALIFICATION;
        $calcType = Cfg::CODE_TYPE_CALC_BONUS;
        $reqGetPeriod->setBaseCalcTypeCode($calcTypeBase);
        $reqGetPeriod->setDependentCalcTypeCode($calcType);
        $respGetPeriod = $this->callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $def = $this->manTrans->begin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend->getId();
                $dsBegin = $periodDataDepend->getDstampBegin();
                $dsEnd = $periodDataDepend->getDstampEnd();
                /* collect data to process bonus */
                $calcTypeIdCompress = $this->repoBonusTypeCalc->getIdByCode(Cfg::CODE_TYPE_CALC_COMPRESSION);
                $calcDataCompress = $this->repoBonusService
                    ->getLastCalcForPeriodByDates($calcTypeIdCompress, $dsBegin, $dsEnd);
                $calcIdCompress = $calcDataCompress->getId();
                $params = $this->repoMod->getConfigParams();
                $percents = $this->repoMod->getBonusPercents();
                $treeCompressed = $this->repoMod->getCompressedTreeWithQualifications($calcIdCompress);
                $orders = $this->repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
                /* calculate bonus */
                $updates = $this->subBonus->calc($treeCompressed, $orders, $params, $percents);
                /* create new operation with bonus transactions and save sales log */
                $respAdd = $this->_createBonusOperation($updates);
                $transLog = $respAdd->getTransactionsIds();
                $this->repoMod->saveLogSaleOrders($transLog);
                /* mark calculation as completed and finalize bonus */
                $this->repoBonusService->markCalcComplete($calcIdDepend);
                $this->manTrans->commit($def);
                $result->setPeriodId($periodDataDepend->getId());
                $result->setCalcId($calcIdDepend);
                $result->markSucceed();
            } finally {
                $this->manTrans->end($def);
            }
        }
        $this->logger->info("'Loyalty Bonus' calculation is complete.");
        return $result;
    }

    public function compress(Request\Compress $req)
    {
        $result = new Response\Compress();
        $calcTypeCode = Cfg::CODE_TYPE_CALC_COMPRESSION;
        $this->logger->info("'Loyalty Compression' calculation is started.");
        $reqGetLatest = new PeriodGetLatestForPvBasedCalcRequest();
        $reqGetLatest->setCalcTypeCode($calcTypeCode);
        $respGetLatest = $this->callBasePeriod->getForPvBasedCalc($reqGetLatest);
        if ($respGetLatest->isSucceed()) {
            $def = $this->manTrans->begin();
            try {
                /* get tree snapshot and orders data */
                $periodData = $respGetLatest->getPeriodData();
                $dsBegin = $periodData->getDstampBegin();
                $dsEnd = $periodData->getDstampEnd();
                $tree = $this->_getDownlineSnapshot($dsEnd);
                $orders = $this->repoMod->getSalesOrdersForPeriod($dsBegin, $dsEnd);
                /* match orders to customers  */
                foreach ($orders as $order) {
                    $custId = $order[Cfg::E_SALE_ORDER_A_CUSTOMER_ID];
                    $tree[$custId][Sub\CompressQualifier::AS_HAS_ORDERS] = true;
                }
                /* call to compression operation from bonus base module */
                $reqCompress = new BonusBaseQualifyByUserDataRequest();
                $calcData = $respGetLatest->getCalcData();
                $calcId = $calcData->getId();
                $reqCompress->setCalcId($calcId);
                $reqCompress->setFlatTree($tree);
                $reqCompress->setSkipTreeExpand(true);
                $reqCompress->setQualifier(new Sub\CompressQualifier());
                $respCompress = $this->callBaseCompress->qualifyByUserData($reqCompress);
                if ($respCompress->isSucceed()) {
                    $this->repoBonusService->markCalcComplete($calcId);
                    $this->manTrans->commit($def);
                    $result->setPeriodId($periodData->getId());
                    $result->setCalcId($calcId);
                    $result->markSucceed();
                }
            } finally {
                $this->manTrans->end($def);
            }
        }
        $this->logger->info("'Loyalty Compression' calculation is complete.");
        return $result;
    }

    public function qualification(Request\Qualification $req)
    {
        $result = new Response\Qualification();
        $datePerformed = $req->getDatePerformed();
        $dateApplied = $req->getDateApplied();
        $gvMaxLevels = $req->getGvMaxLevels();
        $psaaLevel = $req->getPsaaLevel();
        $this->logger->info("'Qualification for Loyalty' calculation is started. Performed at: $datePerformed, applied at: $dateApplied.");
        $reqGetPeriod = new PeriodGetForDependentCalcRequest();
        $calcTypeBase = Cfg::CODE_TYPE_CALC_COMPRESSION;
        $calcType = Cfg::CODE_TYPE_CALC_QUALIFICATION;
        $reqGetPeriod->setBaseCalcTypeCode($calcTypeBase);
        $reqGetPeriod->setDependentCalcTypeCode($calcType);
        $respGetPeriod = $this->callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $def = $this->manTrans->begin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend->getId();
                $calcDataBase = $respGetPeriod->getBaseCalcData();
                $dsBegin = $periodDataDepend->getDstampBegin();
                $dsEnd = $periodDataDepend->getDstampEnd();
                $calcIdBase = $calcDataBase->getId();
                $tree = $this->repoBonusCompress->getTreeByCalcId($calcIdBase);
                $qualData = $this->repoMod->getQualificationData($dsBegin, $dsEnd);
                $updates = $this->subQualification->calcParams($tree, $qualData, $gvMaxLevels, $psaaLevel);
                $this->repoMod->saveQualificationParams($updates);
                $this->repoBonusService->markCalcComplete($calcIdDepend);
                $this->manTrans->commit($def);
                $result->setPeriodId($periodDataDepend->getId());
                $result->setCalcId($calcIdDepend);
                $result->markSucceed();
            } finally {
                $this->manTrans->end($def);
            }
        }
        $this->logger->info("'Qualification for Loyalty' calculation is complete.");
        return $result;
    }
}