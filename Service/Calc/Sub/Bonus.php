<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub;

use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Config as Cfg;
use Praxigento\Downline\Repo\Data\Snap;
use Praxigento\Downline\Service\Snap\Request\ExpandMinimal as DownlineSnapExtendMinimalRequest;
use Praxigento\Pv\Repo\Data\Sale as PvSale;

class Bonus {
    /** @var \Praxigento\BonusLoyalty\Service\Calc\Sub\Bonus\RankQualifier */
    private $aRankQualifier;
    /** @var  \Praxigento\Core\Api\Helper\Format */
    private $hlpFormat;
    /** @var  \Praxigento\Downline\Api\Helper\Tree */
    private $hlpTree;
    /** @var   \Praxigento\Downline\Service\ISnap */
    private $servDownlineSnap;

    /**
     * Bonus constructor.
     */
    public function __construct(
        \Praxigento\Downline\Service\ISnap $servDownlineSnap,
        \Praxigento\Core\Api\Helper\Format $hlpFormat,
        \Praxigento\Downline\Api\Helper\Tree $hlpTree,
        \Praxigento\BonusLoyalty\Service\Calc\Sub\Bonus\RankQualifier $aRankQualifier
    ) {
        $this->servDownlineSnap = $servDownlineSnap;
        $this->hlpFormat = $hlpFormat;
        $this->hlpTree = $hlpTree;
        $this->aRankQualifier = $aRankQualifier;
    }

    private function _expandTree($data) {
        $req = new DownlineSnapExtendMinimalRequest();
        $req->setKeyCustomerId(Compress::A_CUSTOMER_ID);
        $req->setKeyParentId(Compress::A_PARENT_ID);
        $req->setTree($data);
        $resp = $this->servDownlineSnap->expandMinimal($req);
        return $resp->getSnapData();
    }

    /**
     * @param array $tree
     * @param array $orders
     * @param array $params configuration parameters ordered desc (from up to down)
     * @param array $percents
     *
     * @return array
     */
    public function calc($tree, $orders, $params, $percents) {
        $result = [ ];
        $mapTreeExp = $this->_expandTree($tree);
        $mapRankById = $this->aRankQualifier->qualifyCustomers($tree, $params);
        foreach($orders as $order) {
            $custId = $order[Cfg::E_SALE_ORDER_A_CUSTOMER_ID];
            $orderId = $order[PvSale::A_SALE_ID];
            $pv = $order[PvSale::A_TOTAL];
            $path = $mapTreeExp[$custId][Snap::A_PATH];
            $parents = $this->hlpTree->getParentsFromPathReversed($path);
            $gen = 1;
            foreach($parents as $parentId) {
                if(isset($mapRankById[$parentId])) {
                    $parentRank = $mapRankById[$parentId];
                    if(isset($percents[$parentRank][$gen])) {
                        $percent = $percents[$parentRank][$gen];
                        $bonus = $pv * $percent;
                        $bonus = $this->hlpFormat->roundBonus($bonus);
                        $result[$parentId][$orderId] = $bonus;
                    }
                }
                $gen++;
            }

        }
        return $result;
    }
}