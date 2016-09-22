<?php
/**
 * Facade for current module for dependent modules repos.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Repo\Def;

use Praxigento\BonusBase\Data\Entity\Cfg\Generation as ECfgGeneration;
use Praxigento\BonusBase\Data\Entity\Compress;
use Praxigento\BonusBase\Repo\IModule as RepoBonusBaseModule;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\BonusLoyalty\Data\Entity\Cfg\Param as CfgParam;
use Praxigento\BonusLoyalty\Data\Entity\Qualification;
use Praxigento\BonusLoyalty\Repo\IModule;
use Praxigento\Core\Repo\Def\Db;
use Praxigento\Pv\Data\Entity\Sale as PvSale;

class Module extends Db implements IModule
{
    /** @var  \Praxigento\Core\Transaction\Database\IManager */
    protected $_manTrans;
    /** @var \Praxigento\Core\Repo\IGeneric */
    protected $_repoBasic;
    /** @var RepoBonusBaseModule */
    protected $_repoBonusBase;
    /** @var \Praxigento\BonusBase\Repo\Entity\Cfg\IGeneration */
    protected $_repoBonusCfgGen;
    /** @var  \Praxigento\BonusBase\Repo\Entity\Log\ISales */
    protected $_repoLogSales;
    /** @var \Praxigento\BonusBase\Repo\Entity\Type\ICalc */
    protected $_repoTypeCalc;
    /** @var  \Praxigento\Core\Tool\IPeriod */
    protected $_toolPeriod;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\Transaction\Database\IManager $manTrans,
        \Praxigento\Core\Repo\IGeneric $repoBasic,
        RepoBonusBaseModule $repoBonusBase,
        \Praxigento\BonusBase\Repo\Entity\Cfg\IGeneration $repoBonusCfgGen,
        \Praxigento\BonusBase\Repo\Entity\Log\ISales $repoLogSales,
        \Praxigento\BonusBase\Repo\Entity\Type\ICalc $repoTypeCalc,
        \Praxigento\Core\Tool\IPeriod $toolPeriod
    ) {
        parent::__construct($resource);
        $this->_manTrans = $manTrans;
        $this->_repoBasic = $repoBasic;
        $this->_repoBonusBase = $repoBonusBase;
        $this->_repoBonusCfgGen = $repoBonusCfgGen;
        $this->_repoLogSales = $repoLogSales;
        $this->_repoTypeCalc = $repoTypeCalc;
        $this->_toolPeriod = $toolPeriod;
    }

    function getBonusPercents()
    {
        $result = [];
        $calcTypeId = $this->_repoTypeCalc->getIdByCode(Cfg::CODE_TYPE_CALC_BONUS);
        $where = ECfgGeneration::ATTR_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $rows = $this->_repoBonusCfgGen->get($where);
        foreach ($rows as $row) {
            $rankId = $row[ECfgGeneration::ATTR_RANK_ID];
            $gen = $row[ECfgGeneration::ATTR_GENERATION];
            $percent = $row[ECfgGeneration::ATTR_PERCENT];
            $result[$rankId][$gen] = $percent;
        }
        return $result;
    }

    /**
     * SELECT
     * pbbc.customer_id,
     * pbbc.parent_id,
     * pblq.pv,
     * pblq.gv,
     * pblq.psaa
     * FROM prxgt_bon_base_compress pbbc
     * LEFT JOIN prxgt_bon_loyal_qual pblq
     * ON pbbc.id = pblq.compress_id
     * WHERE
     * pbbc.calc_id=1;
     *
     * @param $calcId
     */
    public function getCompressedTreeWithQualifications($calcId)
    {
        /* aliases and tables */
        $asCompress = 'pbbc';
        $asQual = 'pblq';
        $tblCompress = $this->_resource->getTableName(Compress::ENTITY_NAME);
        $tblQual = $this->_resource->getTableName(Qualification::ENTITY_NAME);
        // SELECT FROM prxgt_bon_base_compress pbbc
        $query = $this->_conn->select();
        $query->from([$asCompress => $tblCompress], [Compress::ATTR_CUSTOMER_ID, Compress::ATTR_PARENT_ID]);
        // LEFT JOIN prxgt_bon_loyal_qual pblq ON pbbc.id = pblq.compress_id
        $on = "$asCompress." . Compress::ATTR_ID . "=$asQual." . Qualification::ATTR_COMPRESS_ID;
        $cols = [
            Qualification::ATTR_PV,
            Qualification::ATTR_GV,
            Qualification::ATTR_PSAA
        ];
        $query->joinLeft([$asQual => $tblQual], $on, $cols);
        // where
        $where = $asCompress . '.' . Compress::ATTR_CALC_ID . '=' . (int)$calcId;
        $query->where($where);
        // $sql = (string)$query;
        $result = $this->_conn->fetchAll($query);
        return $result;
    }

    function getConfigParams()
    {
        $order = [
            CfgParam::ATTR_PSAA . ' DESC',
            CfgParam::ATTR_GV . ' DESC',
            CfgParam::ATTR_PV . ' DESC'
        ];
        $result = $this->_repoBasic->getEntities(CfgParam::ENTITY_NAME, null, null, $order);
        return $result;
    }

    /**
     * SELECT
     * SUM(total) AS `summary`,
     * `sfo`.`customer_id`
     * FROM `prxgt_pv_sale` AS `pps`
     * LEFT JOIN `sales_flat_order` AS `sfo`
     * ON pps.sale_id = sfo.entity_id
     * WHERE (pps.date_paid >= '2016-01-01 08:00:00'
     * AND pps.date_paid <= '2017-01-01 07:59:59')
     * GROUP BY `sfo`.`customer_id`
     *
     * @param string $dsFrom
     * @param string $dsTo
     *
     * @return array [$custId => $pvSummary, ...]
     */
    function getQualificationData($dsFrom, $dsTo)
    {
        $tsFrom = $this->_toolPeriod->getTimestampFrom($dsFrom);
        $tsTo = $this->_toolPeriod->getTimestampTo($dsTo);
        /* aliases and tables */
        $asPvSales = 'pps';
        $asOrder = 'sfo';
        $asSummary = 'summary';
        $tblPv = $this->_resource->getTableName(PvSale::ENTITY_NAME);
        $tblOrder = $this->_resource->getTableName(Cfg::ENTITY_MAGE_SALES_ORDER);
        // SELECT FROM prxgt_pv_sale pps
        $query = $this->_conn->select();
        $query->from([$asPvSales => $tblPv], [$asSummary => 'SUM(' . PvSale::ATTR_TOTAL . ')']);
        // LEFT JOIN sales_flat_order sfo ON pps.sale_id = sfo.entity_id
        $on = "$asPvSales." . PvSale::ATTR_SALE_ID . "=$asOrder." . Cfg::E_SALE_ORDER_A_ENTITY_ID;
        $cols = [
            Cfg::E_SALE_ORDER_A_CUSTOMER_ID
        ];
        $query->joinLeft([$asOrder => $tblOrder], $on, $cols);
        // where
        $whereFrom = $asPvSales . '.' . PvSale::ATTR_DATE_PAID . '>=' . $this->_conn->quote($tsFrom);
        $whereTo = $asPvSales . '.' . PvSale::ATTR_DATE_PAID . '<=' . $this->_conn->quote($tsTo);
        $query->where("$whereFrom AND $whereTo");
        // group by
        $query->group($asOrder . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID);
        // $sql = (string)$query;
        $items = $this->_conn->fetchAll($query);
        $result = [];
        foreach ($items as $item) {
            $custId = $item[Cfg::E_SALE_ORDER_A_CUSTOMER_ID];
            $pv = $item[$asSummary];
            $result[$custId] = $pv;
        }
        return $result;
    }

    /**
     * SELECT
     * `pps`.`sale_id`,
     * `sfo`.`customer_id`
     * FROM `prxgt_pv_sale` AS `pps`
     * LEFT JOIN `sales_flat_order` AS `sfo`
     * ON pps.sale_id = sfo.entity_id
     * WHERE (pps.date_paid >= '2016-01-01 08:00:00'
     * AND pps.date_paid <= '2017-01-01 07:59:59')
     *
     * @param string $dsFrom
     * @param string $dsTo
     *
     * @return array
     */
    function getSalesOrdersForPeriod($dsFrom, $dsTo)
    {
        $tsFrom = $this->_toolPeriod->getTimestampFrom($dsFrom);
        $tsTo = $this->_toolPeriod->getTimestampTo($dsTo);
        /* aliases and tables */
        $asPv = 'pps';
        $asOrder = 'sfo';
        $tblPv = $this->_resource->getTableName(PvSale::ENTITY_NAME);
        $tblOrder = $this->_resource->getTableName(Cfg::ENTITY_MAGE_SALES_ORDER);
        // SELECT FROM prxgt_pv_sale pps
        $query = $this->_conn->select();
        $query->from([$asPv => $tblPv], [PvSale::ATTR_SALE_ID, PvSale::ATTR_TOTAL]);
        // LEFT JOIN sales_flat_order sfo ON pps.sale_id = sfo.entity_id
        $on = "$asPv." . PvSale::ATTR_SALE_ID . "=$asOrder." . Cfg::E_SALE_ORDER_A_ENTITY_ID;
        $cols = [
            Cfg::E_SALE_ORDER_A_CUSTOMER_ID
        ];
        $query->joinLeft([$asOrder => $tblOrder], $on, $cols);
        // where
        $whereFrom = $asPv . '.' . PvSale::ATTR_DATE_PAID . '>=' . $this->_conn->quote($tsFrom);
        $whereTo = $asPv . '.' . PvSale::ATTR_DATE_PAID . '<=' . $this->_conn->quote($tsTo);
        $query->where("$whereFrom AND $whereTo");
        // $sql = (string)$query;
        $result = $this->_conn->fetchAll($query);
        return $result;
    }

    public function saveBonus($updates)
    {
        $def = $this->_manTrans->begin();
        try {
            foreach ($updates as $item) {
                $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
            }
            $this->_manTrans->commit($def);
        } finally {
            $this->_manTrans->end($def);
        }
    }

    public function saveLogSaleOrders($updates)
    {
        $def = $this->_manTrans->begin();
        try {
            foreach ($updates as $transId => $saleId) {
                $data = [
                    \Praxigento\BonusBase\Data\Entity\Log\Sales::ATTR_TRANS_ID => $transId,
                    \Praxigento\BonusBase\Data\Entity\Log\Sales::ATTR_SALE_ORDER_ID => $saleId
                ];
                $this->_repoLogSales->create($data);
            }
            $this->_manTrans->commit($def);
        } finally {
            $this->_manTrans->end($def);
        }
    }

    public function saveQualificationParams($updates)
    {
        $def = $this->_manTrans->begin();
        try {
            foreach ($updates as $item) {
                $this->_repoBasic->addEntity(Qualification::ENTITY_NAME, $item);
            }
            $this->_manTrans->commit($def);
        } finally {
            $this->_manTrans->end($def);
        }
    }

}