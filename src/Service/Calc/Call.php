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
use Praxigento\Pv\Data\Entity\Sale as PvSale;
use Praxigento\Wallet\Service\Operation\Request\AddToWalletActive as WalletOperationAddToWalletActiveRequest;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Call
    extends \Praxigento\Core\Service\Base\Call
    implements \Praxigento\BonusLoyalty\Service\ICalc
{
    /** @var  \Praxigento\BonusBase\Service\ICompress */
    protected $_callBaseCompress;
    /** @var  \Praxigento\BonusBase\Service\IPeriod */
    protected $_callBasePeriod;
    /** @var  \Praxigento\Downline\Service\ISnap */
    protected $_callDownlineSnap;
    /** @var  \Praxigento\Wallet\Service\IOperation */
    protected $_callWalletOperation;
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;
    /** @var  \Praxigento\Core\Transaction\Database\IManager */
    protected $_manTrans;
    /** @var  \Praxigento\BonusBase\Repo\Entity\ICompress */
    protected $_repoBonusCompress;
    /** @var \Praxigento\BonusBase\Repo\Service\IModule */
    protected $_repoBonusService;
    /** @var \Praxigento\BonusBase\Repo\Entity\Type\ICalc */
    protected $_repoBonusTypeCalc;
    /** @var \Praxigento\BonusLoyalty\Repo\IModule */
    protected $_repoMod;
    /** @var Sub\Bonus */
    protected $_subBonus;
    /** @var Sub\Qualification */
    protected $_subQualification;

    /**
     * Call constructor.
     * @param \Praxigento\Core\Fw\Logger\App $logger
     * @param \Magento\Framework\ObjectManagerInterface $manObj
     * @param \Praxigento\Core\Transaction\Database\IManager $manTrans
     * @param \Praxigento\BonusLoyalty\Repo\IModule $repoMod
     * @param \Praxigento\BonusBase\Repo\Service\IModule $repoBonusService
     * @param \Praxigento\BonusBase\Repo\Entity\ICompress $repoBonusCompress
     * @param \Praxigento\BonusBase\Repo\Entity\Type\ICalc $repoBonusTypeCalc
     * @param \Praxigento\BonusBase\Service\ICompress $callBaseCompress
     * @param \Praxigento\BonusBase\Service\IPeriod $callBasePeriod
     * @param \Praxigento\Downline\Service\ISnap $callDownlineSnap
     * @param \Praxigento\Wallet\Service\IOperation $callWalletOperation
     * @param Sub\Bonus $subBonus
     * @param Sub\Qualification $subQualification
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Praxigento\Core\Fw\Logger\App $logger,
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Praxigento\Core\Transaction\Database\IManager $manTrans,
        \Praxigento\BonusLoyalty\Repo\IModule $repoMod,
        \Praxigento\BonusBase\Repo\Service\IModule $repoBonusService,
        \Praxigento\BonusBase\Repo\Entity\ICompress $repoBonusCompress,
        \Praxigento\BonusBase\Repo\Entity\Type\ICalc $repoBonusTypeCalc,
        \Praxigento\BonusBase\Service\ICompress $callBaseCompress,
        \Praxigento\BonusBase\Service\IPeriod $callBasePeriod,
        \Praxigento\Downline\Service\ISnap $callDownlineSnap,
        \Praxigento\Wallet\Service\IOperation $callWalletOperation,
        Sub\Bonus $subBonus,
        Sub\Qualification $subQualification
    ) {
        parent::__construct($logger, $manObj);
        $this->_manTrans = $manTrans;
        $this->_repoMod = $repoMod;
        $this->_repoBonusService = $repoBonusService;
        $this->_repoBonusCompress = $repoBonusCompress;
        $this->_repoBonusTypeCalc = $repoBonusTypeCalc;
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
        $result = $this->_callWalletOperation->addToWalletActive($req);
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
        $resp = $this->_callDownlineSnap->getStateOnDate($req);
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
        $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $def = $this->_manTrans->begin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend->getId();
                $dsBegin = $periodDataDepend->getDstampBegin();
                $dsEnd = $periodDataDepend->getDstampEnd();
                /* collect data to process bonus */
                $calcTypeIdCompress = $this->_repoBonusTypeCalc->getIdByCode(Cfg::CODE_TYPE_CALC_COMPRESSION);
                $calcDataCompress = $this->_repoBonusService
                    ->getLastCalcForPeriodByDates($calcTypeIdCompress, $dsBegin, $dsEnd);
                $calcIdCompress = $calcDataCompress->getId();
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
                $this->_repoBonusService->markCalcComplete($calcIdDepend);
                $this->_manTrans->commit($def);
                $result->setPeriodId($periodDataDepend->getId());
                $result->setCalcId($calcIdDepend);
                $result->markSucceed();
            } finally {
                $this->_manTrans->end($def);
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
        $respGetLatest = $this->_callBasePeriod->getForPvBasedCalc($reqGetLatest);
        if ($respGetLatest->isSucceed()) {
            $def = $this->_manTrans->begin();
            try {
                /* get tree snapshot and orders data */
                $periodData = $respGetLatest->getPeriodData();
                $dsBegin = $periodData->getDstampBegin();
                $dsEnd = $periodData->getDstampEnd();
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
                $calcId = $calcData->getId();
                $reqCompress->setCalcId($calcId);
                $reqCompress->setFlatTree($tree);
                $reqCompress->setSkipTreeExpand(true);
                $reqCompress->setQualifier(new Sub\CompressQualifier());
                $respCompress = $this->_callBaseCompress->qualifyByUserData($reqCompress);
                if ($respCompress->isSucceed()) {
                    $this->_repoBonusService->markCalcComplete($calcId);
                    $this->_manTrans->commit($def);
                    $result->setPeriodId($periodData->getId());
                    $result->setCalcId($calcId);
                    $result->markSucceed();
                }
            } finally {
                $this->_manTrans->end($def);
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
        $respGetPeriod = $this->_callBasePeriod->getForDependentCalc($reqGetPeriod);
        if ($respGetPeriod->isSucceed()) {
            $def = $this->_manTrans->begin();
            try {
                $periodDataDepend = $respGetPeriod->getDependentPeriodData();
                $calcDataDepend = $respGetPeriod->getDependentCalcData();
                $calcIdDepend = $calcDataDepend->getId();
                $calcDataBase = $respGetPeriod->getBaseCalcData();
                $dsBegin = $periodDataDepend->getDstampBegin();
                $dsEnd = $periodDataDepend->getDstampEnd();
                $calcIdBase = $calcDataBase->getId();
                $tree = $this->_repoBonusCompress->getTreeByCalcId($calcIdBase);
                $qualData = $this->_repoMod->getQualificationData($dsBegin, $dsEnd);
                $updates = $this->_subQualification->calcParams($tree, $qualData, $gvMaxLevels, $psaaLevel);
                $this->_repoMod->saveQualificationParams($updates);
                $this->_repoBonusService->markCalcComplete($calcIdDepend);
                $this->_manTrans->commit($def);
                $result->setPeriodId($periodDataDepend->getId());
                $result->setCalcId($calcIdDepend);
                $result->markSucceed();
            } finally {
                $this->_manTrans->end($def);
            }
        }
        $this->logger->info("'Qualification for Loyalty' calculation is complete.");
        return $result;
    }
}