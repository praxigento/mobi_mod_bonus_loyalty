<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub\Bonus;

use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Repo\Data\Cfg\Param as CfgParam;
use Praxigento\BonusLoyalty\Repo\Data\Qualification;

class RankQualifier {

    public function qualifyCustomers($customers, $params) {
        $result = [ ];
        foreach($customers as $customer) {
            $custId = $customer[Compress::A_CUSTOMER_ID];
            $pv = $customer[Qualification::A_PV];
            $gv = $customer[Qualification::A_GV];
            $psaa = $customer[Qualification::A_PSAA];
            foreach($params as $param) {
                $qPv = $param[CfgParam::A_PV];
                $qGv = $param[CfgParam::A_GV];
                $qPsaa = $param[CfgParam::A_PSAA];
                if(
                    ($pv >= $qPv) &&
                    ($gv >= $qGv) &&
                    ($psaa >= $qPsaa)
                ) {
                    $result[$custId] = $param[CfgParam::A_RANK_ID];
                    break;
                }
            }
        }
        return $result;
    }
}