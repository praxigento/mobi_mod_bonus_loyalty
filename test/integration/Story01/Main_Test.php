<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Lib\Test\Story01;

use Praxigento\Accounting\Data\Entity\Account;
use Praxigento\BonusBase\Data\Entity\Calculation;
use Praxigento\BonusBase\Data\Entity\Cfg\Generation;
use Praxigento\BonusBase\Data\Entity\Compress;
use Praxigento\BonusBase\Data\Entity\Rank;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\BonusLoyalty\Data\Entity\Cfg\Param;
use Praxigento\BonusLoyalty\Data\Entity\Qualification;
use Praxigento\BonusLoyalty\Service\Calc\Request\Bonus as LoyaltyCalcBonusRequest;
use Praxigento\BonusLoyalty\Service\Calc\Request\Compress as LoyaltyCalcCompressRequest;
use Praxigento\BonusLoyalty\Service\Calc\Request\Qualification as LoyaltyCalcQualificationRequest;
use Praxigento\Core\Test\BaseIntegrationTest;
use Praxigento\Pv\Data\Entity\Sale as PvSale;
use Praxigento\Pv\Service\Sale\Request\AccountPv as PvSaleAccountPvRequest;

include_once(__DIR__ . '/../phpunit_bootstrap.php');

class Main_IntegrationTest extends BaseIntegrationTest
{
    const DS_DOWNLINE_SNAP_UP_TO = '20160201';
    const DS_ORDERS_CREATED = '20160115';
    const DS_PERIOD_BEGIN = '20160101';
    const RANK_BY_GV = 'QUALIFIED_BY_PV_GV';
    const RANK_BY_PSAA = 'QUALIFIED_BY_PV_GV_PSAA';
    const RANK_BY_PV = 'QUALIFIED_BY_PV';
    /**
     * Hardcoded data for orders: [$custNdx => $pv, ...]
     * @var array
     */
    private $DEF_ORDERS = [
        1 => 5000,
        2 => 500,
        3 => 5000,
        4 => 120,
        5 => 200,
        6 => 200,
        7 => null,
        8 => 200,
        9 => 200,
        10 => null,
        11 => 200,
        12 => 120,
        13 => 100
    ];
    /** @var \Praxigento\BonusLoyalty\Service\ICalc */
    private $_callLoyaltyCalc;
    /** @var  \Praxigento\Pv\Service\ISale */
    private $_callPvSale;
    /** @var   \Praxigento\Accounting\Repo\IModule */
    private $_repoAcc;
    /** @var \Praxigento\BonusBase\Repo\IModule */
    private $_repoBase;
    /** @var \Praxigento\BonusBase\Repo\Entity\Type\ICalc */
    private $_repoBonusTypeCalc;
    /** @var \Praxigento\Core\Repo\IGeneric */
    private $_repoCore;
    /** @var  \Praxigento\Accounting\Repo\Entity\Type\IAsset */
    private $_repoTypeAsset;

    public function __construct()
    {
        parent::__construct();
        $this->_callPvSale = $this->_manObj->get(\Praxigento\Pv\Service\ISale::class);
        $this->_callLoyaltyCalc = $this->_manObj->get(\Praxigento\BonusLoyalty\Service\ICalc::class);
        $this->_repoCore = $this->_manObj->get(\Praxigento\Core\Repo\IGeneric::class);
        $this->_repoBase = $this->_manObj->get(\Praxigento\BonusBase\Repo\IModule::class);
        $this->_repoBonusTypeCalc = $this->_manObj->get(\Praxigento\BonusBase\Repo\Entity\Type\ICalc::class);
        $this->_repoTypeAsset = $this->_manObj->get(\Praxigento\Accounting\Repo\Entity\Type\IAsset::class);
        $this->_repoAcc = $this->_manObj->get(\Praxigento\Accounting\Repo\IModule::class);
    }

    private function _calcBonus()
    {
        $req = new LoyaltyCalcBonusRequest();
        $resp = $this->_callLoyaltyCalc->bonus($req);
        $this->assertTrue($resp->isSucceed());
        $calcId = $resp->getCalcId();
        /* validate calculation state  */
        $data = $this->_repoCore->getEntityByPk(Calculation::ENTITY_NAME, [Calculation::ATTR_ID => $calcId]);
        $this->assertEquals(Cfg::CALC_STATE_COMPLETE, $data[Calculation::ATTR_STATE]);
        return $calcId;
    }

    private function _calcCompression()
    {
        $req = new LoyaltyCalcCompressRequest();
        $resp = $this->_callLoyaltyCalc->compress($req);
        $this->assertTrue($resp->isSucceed());
        $calcId = $resp->getCalcId();
        /* validate calculation state  */
        $data = $this->_repoCore->getEntityByPk(Calculation::ENTITY_NAME, [Calculation::ATTR_ID => $calcId]);
        $this->assertEquals(Cfg::CALC_STATE_COMPLETE, $data[Calculation::ATTR_STATE]);
        return $calcId;
    }

    private function _calcQualification()
    {
        $req = new LoyaltyCalcQualificationRequest();
        $req->setGvMaxLevels(2);
        $req->setPsaaLevel(120);
        $resp = $this->_callLoyaltyCalc->qualification($req);
        $this->assertTrue($resp->isSucceed());
        $calcId = $resp->getCalcId();
        /* validate calculation state  */
        $data = $this->_repoCore->getEntityByPk(Calculation::ENTITY_NAME, [Calculation::ATTR_ID => $calcId]);
        $this->assertEquals(Cfg::CALC_STATE_COMPLETE, $data[Calculation::ATTR_STATE]);
        return $calcId;
    }

    private function _createOrders()
    {
        foreach ($this->DEF_ORDERS as $custNdx => $pv) {
            if (is_null($pv)) {
                continue;
            }
            $custId = $this->_mapCustomerMageIdByIndex[$custNdx];
            $ts = $this->_toolPeriod->getTimestampTo(self::DS_ORDERS_CREATED);
            $bindOrder = [
                Cfg::E_SALE_ORDER_A_CUSTOMER_ID => $custId,
                Cfg::E_SALE_ORDER_A_BASE_GRAND_TOTAL => $pv,
                Cfg::E_SALE_ORDER_A_CREATED_AT => $ts,
                Cfg::E_SALE_ORDER_A_UPDATED_AT => $ts
            ];
            $orderId = $this->_repoCore->addEntity(Cfg::ENTITY_MAGE_SALES_ORDER, $bindOrder);
            $bindPv = [
                PvSale::ATTR_SALE_ID => $orderId,
                PvSale::ATTR_SUBTOTAL => $pv,
                PvSale::ATTR_TOTAL => $pv,
                PvSale::ATTR_DATE_PAID => $ts
            ];
            $this->_repoCore->addEntity(PvSale::ENTITY_NAME, $bindPv);
            $this->_logger->debug("New PV sale on $pv PV paid at $ts is registered for order #$orderId and customer #$custId .");
            $this->_createPvTransaction($custId, $orderId, $ts);
        }
    }

    /**
     * Register PV transaction for sale order.
     */
    private function _createPvTransaction($custId, $orderId, $dateApplied)
    {
        $req = new PvSaleAccountPvRequest();
        $req->setCustomerId($custId);
        $req->setSaleOrderId($orderId);
        $req->setDateApplied($dateApplied);
        $resp = $this->_callPvSale->accountPv($req);
        if ($resp->isSucceed()) {
            $this->_logger->debug("New PV transaction is registered for order #$orderId and customer #$custId .");
        }
    }

    private function _repoGetBalances()
    {
        $assetTypeId = $this->_repoTypeAsset->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET_ACTIVE);
        $where = Account::ATTR_ASSET_TYPE_ID . '=' . (int)$assetTypeId;
        $result = $this->_repoCore->getEntities(Account::ENTITY_NAME, null, $where);
        return $result;
    }

    /**
     * SELECT
     * pbbc.customer_id,
     * pbbc.parent_id,
     * pblq.*
     * FROM prxgt_bon_base_compress pbbc
     * LEFT JOIN prxgt_bon_loyal_qual pblq
     * ON pbbc.id = pblq.compress_id
     * WHERE pbbc.calc_id = 1
     *
     * @param $calcId
     * @return array
     */
    private function _repoGetQualificationData($calcId)
    {
        /* aliases and tables */
        $asCompress = 'pbbc';
        $asQual = 'pblq';
        $tblCompress = $this->_resource->getTableName(Compress::ENTITY_NAME);
        $tblQual = $this->_resource->getTableName(Qualification::ENTITY_NAME);
        /* SELECT  FROM prxgt_bon_base_compress pbbc */
        $query = $this->_conn->select();
        $query->from([$asCompress => $tblCompress], [Compress::ATTR_CUSTOMER_ID]);
        /* LEFT JOIN prxgt_bon_loyal_qual pblq ON pbbc.id = pblq.compress_id */
        $on = $asCompress . '.' . Compress::ATTR_ID . "=$asQual." . Qualification::ATTR_COMPRESS_ID;
        $cols = '*';
        $query->joinLeft([$asQual => $tblQual], $on, $cols);
        /* where  */
        $where = $asCompress . '.' . Compress::ATTR_CALC_ID . '=' . (int)$calcId;
        //$query->where($where);
        // $sql = (string)$query;
        $result = $this->_conn->fetchAll($query);
        return $result;
    }

    private function _setParams()
    {
        $PARAMS = [
            self::RANK_BY_PV => [Param::ATTR_PV => 120],
            self::RANK_BY_GV => [Param::ATTR_PV => 120, Param::ATTR_GV => 500],
            self::RANK_BY_PSAA => [Param::ATTR_PV => 120, Param::ATTR_GV => 2000, Param::ATTR_PSAA => 2]
        ];
        foreach ($PARAMS as $rank => $bind) {
            $rankId = $this->_repoBase->getRankIdByCode($rank);
            $bind [Param::ATTR_RANK_ID] = $rankId;
            $this->_repoCore->addEntity(Param::ENTITY_NAME, $bind);
        }
        $this->_logger->debug("Configuration parameters for Loyalty bonus are set.");
    }

    private function _setPercents()
    {
        $PERCENTS = [
            self::RANK_BY_PV => [0.05],
            self::RANK_BY_GV => [0.10],
            self::RANK_BY_PSAA => [0.15, 0.10]
        ];
        foreach ($PERCENTS as $rank => $percents) {
            $calcTypeId = $this->_repoBonusTypeCalc->getIdByCode(Cfg::CODE_TYPE_CALC_BONUS);
            $rankId = $this->_repoBase->getRankIdByCode($rank);
            $bind = [
                Generation::ATTR_RANK_ID => $rankId,
                Generation::ATTR_CALC_TYPE_ID => $calcTypeId
            ];
            $gen = 1;
            foreach ($percents as $percent) {
                $bind[Generation::ATTR_PERCENT] = $percent;
                $bind[Generation::ATTR_GENERATION] = $gen++;
                $this->_repoCore->addEntity(Generation::ENTITY_NAME, $bind);
            }
        }
        $this->_logger->debug("Configuration parameters for Loyalty bonus are set.");
    }

    private function _setRanks()
    {
        $this->_repoCore->addEntity(Rank::ENTITY_NAME, [
            Rank::ATTR_CODE => self::RANK_BY_PV,
            Rank::ATTR_NOTE => 'Customer is qualified by PV only.'
        ]);
        $this->_repoCore->addEntity(Rank::ENTITY_NAME, [
            Rank::ATTR_CODE => self::RANK_BY_GV,
            Rank::ATTR_NOTE => 'Customer is qualified by PV and GV.'
        ]);
        $this->_repoCore->addEntity(Rank::ENTITY_NAME, [
            Rank::ATTR_CODE => self::RANK_BY_PSAA,
            Rank::ATTR_NOTE => 'Customer is qualified by PV, GV and PSAA.'
        ]);
        $this->_logger->debug("Ranks for Loyalty bonus are set.");
    }

    private function _validateBonus($calcId)
    {
        $EXP_COUNT = 5; // 4 customers + 1 representative
        $EXP_REPR = -1017.00;
        /* [$custNdx => [$pv, $gv, $psaa], ... ] */
        $EXP_TREE = [
            1 => 919.00,
            2 => 16,
            3 => 62,
            6 => 20
        ];
        $data = $this->_repoGetBalances();
        $this->assertEquals($EXP_COUNT, count($data));
        foreach ($data as $item) {
            $custId = $item[Account::ATTR_CUST_ID];
            $balance = $item[Account::ATTR_BALANCE];
            if ($balance < 0) {
                /* representative */
                $this->assertEquals($EXP_REPR, $balance);
            } else {
                $custNdx = $this->_mapCustomerIndexByMageId[$custId];
                $this->assertEquals($EXP_TREE[$custNdx], $balance);
            }
        }
    }

    private function _validateCompression($calcId)
    {
        $EXP_COUNT = 11;
        $EXP_TREE = [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
            6 => 3,
            8 => 6,
            9 => 6,
            11 => 3,
            12 => 3,
            13 => 3
        ];
        $where = Compress::ATTR_CALC_ID . '=' . $calcId;
        $data = $this->_repoCore->getEntities(Compress::ENTITY_NAME, null, $where);
        $this->assertEquals($EXP_COUNT, count($data));
        foreach ($data as $item) {
            $custId = $item[Compress::ATTR_CUSTOMER_ID];
            $parentId = $item[Compress::ATTR_PARENT_ID];
            $custNdx = $this->_mapCustomerIndexByMageId[$custId];
            $parentNdx = $this->_mapCustomerIndexByMageId[$parentId];
            $this->assertEquals($EXP_TREE[$custNdx], $parentNdx);
        }
    }

    private function _validateQualification($calcId)
    {
        $EXP_COUNT = 11;
        /* [$custNdx => [$pv, $gv, $psaa], ... ] */
        $EXP_TREE = [
            1 => [5000, 6440, 2],
            2 => [500, 320, 1],
            3 => [5000, 1020, 2],
            4 => [120, 0, 0],
            5 => [200, 0, 0],
            6 => [200, 400, 2],
            8 => [200, 0, 0],
            9 => [200, 0, 0],
            11 => [200, 0, 0],
            12 => [120, 0, 0],
            13 => [100, 0, 0]
        ];
        $data = $this->_repoGetQualificationData($calcId);
        $this->assertEquals($EXP_COUNT, count($data));
        foreach ($data as $item) {
            $custId = $item[Compress::ATTR_CUSTOMER_ID];
            $pv = +$item[Qualification::ATTR_PV];
            $gv = +$item[Qualification::ATTR_GV];
            $psaa = +$item[Qualification::ATTR_PSAA];
            $custNdx = $this->_mapCustomerIndexByMageId[$custId];
            $this->assertEquals($EXP_TREE[$custNdx][0], $pv);
            $this->assertEquals($EXP_TREE[$custNdx][1], $gv);
            $this->assertEquals($EXP_TREE[$custNdx][2], $psaa);
        }
    }

    public function setUp()
    {
        parent::setUp();
        /* clear cached data */
        $this->_callPvSale->cacheReset();
    }

    public function test_main()
    {
        $this->_logger->debug('Story01 in Loyalty Bonus Integration tests is started.');
        $this->_conn->beginTransaction();
        try {
            /* set up configuration parameters */
            $this->_setRanks();
            $this->_setParams();
            $this->_setPercents();
            /* create customers and orders */
            $this->_createMageCustomers(13);
            $this->_createDownlineCustomers(self::DS_PERIOD_BEGIN, true);
            $this->_createDownlineSnapshots(self::DS_DOWNLINE_SNAP_UP_TO);
            $this->_createOrders();
            /* compress downline tree for Loyalty bonus */
            $calcIdCompress = $this->_calcCompression();
            $this->_validateCompression($calcIdCompress);
            /* calculate qualification parameters (PV, GV, PSAA) */
            $this->_calcQualification();
            $this->_validateQualification($calcIdCompress);
            /* calculate bonus */
            $calcIdBonus = $this->_calcBonus();
            $this->_validateBonus($calcIdBonus);
        } finally {
            // $this->_conn->commit();
            $this->_conn->rollBack();
        }
        $this->_logger->debug('Story01 in Loyalty Bonus Integration tests is completed, all transactions are rolled back.');
    }
}