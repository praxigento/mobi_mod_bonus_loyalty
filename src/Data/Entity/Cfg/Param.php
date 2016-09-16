<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Data\Entity\Cfg;


class Param
    extends \Praxigento\Core\Data\Entity\Base
{
    const ATTR_GV = 'gv';
    const ATTR_PSAA = 'psaa';
    const ATTR_PV = 'pv';
    const ATTR_RANK_ID = 'rank_id';
    const ENTITY_NAME = 'prxgt_bon_loyal_cfg_param';

    /**
     * @return double
     */
    public function getGv()
    {
        $result = parent::getData(self::ATTR_GV);
        return $result;
    }

    public function getPrimaryKeyAttrs()
    {
        return [self::ATTR_RANK_ID];
    }

    /**
     * @return int
     */
    public function getPsaa()
    {
        $result = parent::getData(self::ATTR_PSAA);
        return $result;
    }

    /**
     * @return double
     */
    public function getPv()
    {
        $result = parent::getData(self::ATTR_PV);
        return $result;
    }

    /**
     * @return int
     */
    public function getRankId()
    {
        $result = parent::getData(self::ATTR_RANK_ID);
        return $result;
    }

    /**
     * @param double $data
     */
    public function setGv($data)
    {
        parent::setData(self::ATTR_GV, $data);
    }

    /**
     * @param int $data
     */
    public function setPsaa($data)
    {
        parent::setData(self::ATTR_PSAA, $data);
    }

    /**
     * @param double $data
     */
    public function setPv($data)
    {
        parent::setData(self::ATTR_PV, $data);
    }

    /**
     * @param int $data
     */
    public function setRankId($data)
    {
        parent::setData(self::ATTR_RANK_ID, $data);
    }
}