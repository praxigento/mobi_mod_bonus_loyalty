<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub\Bonus;

use Praxigento\BonusBase\Data\Entity\Compress;
use Praxigento\BonusLoyalty\Data\Entity\Cfg\Param as CfgParam;
use Praxigento\BonusLoyalty\Data\Entity\Qualification;

class RankQualifier {

    public function qualifyCustomers($customers, $params) {
        $result = [ ];
        foreach($customers as $customer) {
            $custId = $customer[Compress::ATTR_CUSTOMER_ID];
            $pv = $customer[Qualification::ATTR_PV];
            $gv = $customer[Qualification::ATTR_GV];
            $psaa = $customer[Qualification::ATTR_PSAA];
            foreach($params as $param) {
                $qPv = $param[CfgParam::ATTR_PV];
                $qGv = $param[CfgParam::ATTR_GV];
                $qPsaa = $param[CfgParam::ATTR_PSAA];
                if(
                    ($pv >= $qPv) &&
                    ($gv >= $qGv) &&
                    ($psaa >= $qPsaa)
                ) {
                    $result[$custId] = $param[CfgParam::ATTR_RANK_ID];
                    break;
                }
            }
        }
        return $result;
    }
}