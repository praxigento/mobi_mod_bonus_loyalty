<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Data\Entity;

class Qualification
    extends \Praxigento\Core\Data\Entity\Base
{
    const ATTR_COMPRESS_ID = 'compress_id';
    const ATTR_GV = 'gv';
    const ATTR_PSAA = 'psaa';
    const ATTR_PV = 'pv';
    const ENTITY_NAME = 'prxgt_bon_loyal_qual';

    public function getPrimaryKeyAttrs()
    {
        return [self::ATTR_COMPRESS_ID];
    }
}