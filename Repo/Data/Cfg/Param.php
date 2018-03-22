<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Repo\Data\Cfg;


class Param
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
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
        $result = parent::get(self::ATTR_GV);
        return $result;
    }

    public static function getPrimaryKeyAttrs()
    {
        return [self::ATTR_RANK_ID];
    }

    /**
     * @return int
     */
    public function getPsaa()
    {
        $result = parent::get(self::ATTR_PSAA);
        return $result;
    }

    /**
     * @return double
     */
    public function getPv()
    {
        $result = parent::get(self::ATTR_PV);
        return $result;
    }

    /**
     * @return int
     */
    public function getRankId()
    {
        $result = parent::get(self::ATTR_RANK_ID);
        return $result;
    }

    /**
     * @param double $data
     */
    public function setGv($data)
    {
        parent::set(self::ATTR_GV, $data);
    }

    /**
     * @param int $data
     */
    public function setPsaa($data)
    {
        parent::set(self::ATTR_PSAA, $data);
    }

    /**
     * @param double $data
     */
    public function setPv($data)
    {
        parent::set(self::ATTR_PV, $data);
    }

    /**
     * @param int $data
     */
    public function setRankId($data)
    {
        parent::set(self::ATTR_RANK_ID, $data);
    }
}