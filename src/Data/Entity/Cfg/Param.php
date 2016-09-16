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

    public function getPrimaryKeyAttrs()
    {
        return [self::ATTR_RANK_ID];
    }
}