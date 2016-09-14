<?php
/**
 * This qualifier is used in \Praxigento\BonusBase\Lib\Service\ICompress::qualifyByUserData operation.
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Bonus\Loyalty\Lib\Service\Calc\Sub;

class CompressQualifier implements \Praxigento\BonusBase\Lib\Tool\IQualifyUser {
    const AS_HAS_ORDERS = 'HasOrders';

    public function isQualified(array $data) {
        $result = isset($data[self::AS_HAS_ORDERS]);
        return $result;
    }
}