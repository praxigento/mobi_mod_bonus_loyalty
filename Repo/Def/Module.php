<?php
/**
 * Facade for current module for dependent modules repos.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Repo\Def;

use Praxigento\BonusBase\Repo\Data\Cfg\Generation as ECfgGeneration;
use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\BonusLoyalty\Repo\Data\Cfg\Param as CfgParam;
use Praxigento\BonusLoyalty\Repo\Data\Qualification;
use Praxigento\BonusLoyalty\Repo\IModule;
use Praxigento\Core\App\Repo\Db;
use Praxigento\Pv\Repo\Data\Sale as PvSale;

class Module extends Db implements IModule
{
    /** @var  \Praxigento\Core\Api\App\Repo\Transaction\Manager */
    protected $_manTrans;
    /** @var \Praxigento\Core\Api\App\Repo\Generic */
    protected $_repoBasic;
    /** @var \Praxigento\BonusBase\Repo\Dao\Cfg\Generation */
    protected $_repoBonusCfgGen;
    /** @var  \Praxigento\BonusBase\Repo\Dao\Log\Sales */
    protected $_repoLogSales;
    /** @var \Praxigento\BonusBase\Repo\Dao\Type\Calc */
    protected $_repoTypeCalc;
    /** @var  \Praxigento\Core\Api\Helper\Period */
    protected $_toolPeriod;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\Api\App\Repo\Transaction\Manager $manTrans,
        \Praxigento\Core\Api\App\Repo\Generic $daoBasic,
        \Praxigento\BonusBase\Repo\Dao\Cfg\Generation $daoBonusCfgGen,
        \Praxigento\BonusBase\Repo\Dao\Log\Sales $daoLogSales,
        \Praxigento\BonusBase\Repo\Dao\Type\Calc $daoTypeCalc,
        \Praxigento\Core\Api\Helper\Period $toolPeriod
    ) {
        parent::__construct($resource);
        $this->_manTrans = $manTrans;
        $this->_repoBasic = $daoBasic;
        $this->_repoBonusCfgGen = $daoBonusCfgGen;
        $this->_repoLogSales = $daoLogSales;
        $this->_repoTypeCalc = $daoTypeCalc;
        $this->_toolPeriod = $toolPeriod;
    }

    function getBonusPercents()
    {
        $result = [];
        $calcTypeId = $this->_repoTypeCalc->getIdByCode(Cfg::CODE_TYPE_CALC_BONUS);
        $where = ECfgGeneration::A_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $rows = $this->_repoBonusCfgGen->get($where);
        foreach ($rows as $row) {
            /* TODO: use as object not as array */
            $row = (array)$row->get();
            $rankId = $row[ECfgGeneration::A_RANK_ID];
            $gen = $row[ECfgGeneration::A_GENERATION];
            $percent = $row[ECfgGeneration::A_PERCENT];
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
        $tblCompress = $this->resource->getTableName(Compress::ENTITY_NAME);
        $tblQual = $this->resource->getTableName(Qualification::ENTITY_NAME);
        // SELECT FROM prxgt_bon_base_compress pbbc
        $query = $this->conn->select();
        $query->from([$asCompress => $tblCompress], [Compress::A_CUSTOMER_ID, Compress::A_PARENT_ID]);
        // LEFT JOIN prxgt_bon_loyal_qual pblq ON pbbc.id = pblq.compress_id
        $on = "$asCompress." . Compress::A_ID . "=$asQual." . Qualification::A_COMPRESS_ID;
        $cols = [
            Qualification::A_PV,
            Qualification::A_GV,
            Qualification::A_PSAA
        ];
        $query->joinLeft([$asQual => $tblQual], $on, $cols);
        // where
        $where = $asCompress . '.' . Compress::A_CALC_ID . '=' . (int)$calcId;
        $query->where($where);
        // $sql = (string)$query;
        $result = $this->conn->fetchAll($query);
        return $result;
    }

    function getConfigParams()
    {
        $order = [
            CfgParam::A_PSAA . ' DESC',
            CfgParam::A_GV . ' DESC',
            CfgParam::A_PV . ' DESC'
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
        $tblPv = $this->resource->getTableName(PvSale::ENTITY_NAME);
        $tblOrder = $this->resource->getTableName(Cfg::ENTITY_MAGE_SALES_ORDER);
        // SELECT FROM prxgt_pv_sale pps
        $query = $this->conn->select();
        $query->from([$asPvSales => $tblPv], [$asSummary => 'SUM(' . PvSale::A_TOTAL . ')']);
        // LEFT JOIN sales_flat_order sfo ON pps.sale_id = sfo.entity_id
        $on = "$asPvSales." . PvSale::A_SALE_ID . "=$asOrder." . Cfg::E_SALE_ORDER_A_ENTITY_ID;
        $cols = [
            Cfg::E_SALE_ORDER_A_CUSTOMER_ID
        ];
        $query->joinLeft([$asOrder => $tblOrder], $on, $cols);
        // where
        $whereFrom = $asPvSales . '.' . PvSale::A_DATE_PAID . '>=' . $this->conn->quote($tsFrom);
        $whereTo = $asPvSales . '.' . PvSale::A_DATE_PAID . '<=' . $this->conn->quote($tsTo);
        $query->where("$whereFrom AND $whereTo");
        // group by
        $query->group($asOrder . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID);
        // $sql = (string)$query;
        $items = $this->conn->fetchAll($query);
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
        $tblPv = $this->resource->getTableName(PvSale::ENTITY_NAME);
        $tblOrder = $this->resource->getTableName(Cfg::ENTITY_MAGE_SALES_ORDER);
        // SELECT FROM prxgt_pv_sale pps
        $query = $this->conn->select();
        $query->from([$asPv => $tblPv], [PvSale::A_SALE_ID, PvSale::A_TOTAL]);
        // LEFT JOIN sales_flat_order sfo ON pps.sale_id = sfo.entity_id
        $on = "$asPv." . PvSale::A_SALE_ID . "=$asOrder." . Cfg::E_SALE_ORDER_A_ENTITY_ID;
        $cols = [
            Cfg::E_SALE_ORDER_A_CUSTOMER_ID
        ];
        $query->joinLeft([$asOrder => $tblOrder], $on, $cols);
        // where
        $whereFrom = $asPv . '.' . PvSale::A_DATE_PAID . '>=' . $this->conn->quote($tsFrom);
        $whereTo = $asPv . '.' . PvSale::A_DATE_PAID . '<=' . $this->conn->quote($tsTo);
        $query->where("$whereFrom AND $whereTo");
        // $sql = (string)$query;
        $result = $this->conn->fetchAll($query);
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
                    \Praxigento\BonusBase\Repo\Data\Log\Sales::A_TRANS_ID => $transId,
                    \Praxigento\BonusBase\Repo\Data\Log\Sales::A_SALE_ORDER_ID => $saleId
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