<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Repo;

interface IModule  {

    /**
     * @return array [$rankId=>[$generation=>$percent], ...]
     */
    function getBonusPercents();

    /**
     * @param $calcId
     *
     * @return array [[Compress/*], ...]
     */
    public function getCompressedTree($calcId);

    /**
     * Get compressed tree with qualification data (PV, GV, PSAA, ...).
     *
     * @param $calcId
     *
     * @return array [[Compress/customerId+parentId, Qualification/pv+gv+psaa], ...]
     */
    public function getCompressedTreeWithQualifications($calcId);

    /**
     * Configuration parameters ordered from up to down.
     *
     * @return array [[Cfg\Param/*], ...]
     */
    function getConfigParams();

    /**
     * Adapter for \Praxigento\BonusBase\Lib\Repo\Def\Module::getCalcsForPeriod
     *
     * @param int    $calcTypeId
     * @param string $dsBegin 'YYYYMMDD'
     * @param string $dsEnd 'YYYYMMDD'
     *
     * @return array [Calculation/*]
     */
    public function getLatestCalcForPeriod($calcTypeId, $dsBegin, $dsEnd);

    /**
     * @param string $dsFrom 'YYYYMMDD'
     * @param string $dsTo 'YYYYMMDD'
     *
     * @return array [$custId => $pvSummary, ...]
     */
    function getQualificationData($dsFrom, $dsTo);

    /**
     * @param string $dsFrom 'YYYYMMDD'
     * @param string $dsTo 'YYYYMMDD'
     *
     * @return array [[$custId, $saleId, $pvTotal], ...]
     */
    function getSalesOrdersForPeriod($dsFrom, $dsTo);

    /**
     * Decorator for \Praxigento\BonusBase\Lib\Repo\IModule::getTypeCalcIdByCode
     *
     * @param string $calcTypeCode
     *
     * @return int
     */
    public function getTypeCalcIdByCode($calcTypeCode);

    /**
     * @param array $updates [$custId=>[$orderId=>$amount], ...]
     *
     * @return
     */
    public function saveBonus($updates);

    /**
     * Register bonus transactions for sale orders.
     *
     * @param array $updates [$transId=>$saleId, ...]
     */
    public function saveLogSaleOrders($updates);

    /**
     * @param array $updates [[Qualification/*], ...]
     */
    public function saveQualificationParams($updates);

    /**
     * Decorator for \Praxigento\BonusBase\Lib\Repo\IModule::updateCalcSetComplete
     *
     * @param $calcId
     */
    public function updateCalcSetComplete($calcId);
}