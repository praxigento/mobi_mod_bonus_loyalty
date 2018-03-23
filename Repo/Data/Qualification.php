<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Repo\Data;

class Qualification
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const A_COMPRESS_ID = 'compress_id';
    const A_GV = 'gv';
    const A_PSAA = 'psaa';
    const A_PV = 'pv';
    const ENTITY_NAME = 'prxgt_bon_loyal_qual';

    /**
     * @return int
     */
    public function getCompressId()
    {
        $result = parent::get(self::A_COMPRESS_ID);
        return $result;
    }

    /**
     * @return double
     */
    public function getGv()
    {
        $result = parent::get(self::A_GV);
        return $result;
    }

    public static function getPrimaryKeyAttrs()
    {
        return [self::A_COMPRESS_ID];
    }

    /**
     * @return int
     */
    public function getPsaa()
    {
        $result = parent::get(self::A_PSAA);
        return $result;
    }

    /**
     * @return double
     */
    public function getPv()
    {
        $result = parent::get(self::A_PV);
        return $result;
    }

    /**
     * @param int $data
     */
    public function setCompressId($data)
    {
        parent::set(self::A_COMPRESS_ID, $data);
    }

    /**
     * @param double $data
     */
    public function setGv($data)
    {
        parent::set(self::A_GV, $data);
    }

    /**
     * @param int $data
     */
    public function setPsaa($data)
    {
        parent::set(self::A_PSAA, $data);
    }

    /**
     * @param double $data
     */
    public function setPv($data)
    {
        parent::set(self::A_PV, $data);
    }

}