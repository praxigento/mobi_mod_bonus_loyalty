<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Repo\Data;

class Qualification
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const ATTR_COMPRESS_ID = 'compress_id';
    const ATTR_GV = 'gv';
    const ATTR_PSAA = 'psaa';
    const ATTR_PV = 'pv';
    const ENTITY_NAME = 'prxgt_bon_loyal_qual';

    /**
     * @return int
     */
    public function getCompressId()
    {
        $result = parent::get(self::ATTR_COMPRESS_ID);
        return $result;
    }

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
        return [self::ATTR_COMPRESS_ID];
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
     * @param int $data
     */
    public function setCompressId($data)
    {
        parent::set(self::ATTR_COMPRESS_ID, $data);
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

}