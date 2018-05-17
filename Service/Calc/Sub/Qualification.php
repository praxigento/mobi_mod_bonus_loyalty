<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub;

use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Repo\Data\Qualification as EntityQual;
use Praxigento\Downline\Repo\Data\Snap;
use Praxigento\Downline\Service\Map\Request\ById as DownlineMapByIdRequest;
use Praxigento\Downline\Service\Map\Request\TreeByDepth as DownlineMapTreeByDepthRequest;
use Praxigento\Downline\Service\Map\Request\TreeByTeams as DownlineMapTreeByTeamsRequest;
use Praxigento\Downline\Service\Snap\Request\ExpandMinimal as DownlineSnapExtendMinimalRequest;

class Qualification {

    /** @var \Praxigento\Downline\Api\Helper\Tree */
    private $hlpTree;
    /** @var  \Praxigento\Downline\Service\IMap */
    private $servDownlineMap;
    /** @var   \Praxigento\Downline\Service\ISnap */
    private $servDownlineSnap;

    public function __construct(
        \Praxigento\Downline\Service\IMap $servDownlineMap,
        \Praxigento\Downline\Service\ISnap $servDownlineSnap,
        \Praxigento\Downline\Api\Helper\Tree $hlpTree
    ) {
        $this->servDownlineMap = $servDownlineMap;
        $this->servDownlineSnap = $servDownlineSnap;
        $this->hlpTree = $hlpTree;
    }

    private function _expandTree($data) {
        $req = new DownlineSnapExtendMinimalRequest();
        $req->setKeyCustomerId(Compress::A_CUSTOMER_ID);
        $req->setKeyParentId(Compress::A_PARENT_ID);
        $req->setTree($data);
        $resp = $this->servDownlineSnap->expandMinimal($req);
        return $resp->getSnapData();
    }

    private function _initEntry() {
        $result = [
            EntityQual::A_COMPRESS_ID => 0,
            EntityQual::A_PV          => 0,
            EntityQual::A_GV          => 0,
            EntityQual::A_PSAA        => 0
        ];
        return $result;
    }

    private function _mapById($tree) {
        $req = new DownlineMapByIdRequest();
        $req->setDataToMap($tree);
        $req->setAsId(Compress::A_CUSTOMER_ID);
        $resp = $this->servDownlineMap->byId($req);
        return $resp->getMapped();
    }

    private function _mapByTeams($tree) {
        $req = new DownlineMapTreeByTeamsRequest();
        $req->setAsCustomerId(Compress::A_CUSTOMER_ID);
        $req->setAsParentId(Compress::A_PARENT_ID);
        $req->setDataToMap($tree);
        $resp = $this->servDownlineMap->treeByTeams($req);
        return $resp->getMapped();
    }

    private function _mapByTreeDepthDesc($tree) {
        $req = new DownlineMapTreeByDepthRequest();
        $req->setDataToMap($tree);
        $req->setAsCustomerId(Compress::A_CUSTOMER_ID);
        $req->setAsDepth(Snap::A_DEPTH);
        $req->setShouldReversed(true);
        $resp = $this->servDownlineMap->treeByDepth($req);
        return $resp->getMapped();
    }

    public function calcParams($tree, $qData, $gvMaxLevels, $psaaLevel) {
        $treeExpanded = $this->_expandTree($tree);
        $mapByDepth = $this->_mapByTreeDepthDesc($treeExpanded);
        $mapTeams = $this->_mapByTeams($tree);
        $mapById = $this->_mapById($tree);
        $result = [ ];
        foreach($mapByDepth as $depth => $level) {
            foreach($level as $custId) {
                /* init result entry for the customer if entry is not exist */
                if(!isset($result[$custId])) {
                    $result[$custId] = $this->_initEntry();
                    $result[$custId][EntityQual::A_COMPRESS_ID] = $mapById[$custId][Compress::A_ID];
                }
                /* process PV */
                $pv = $qData[$custId];
                $result[$custId][EntityQual::A_PV] = $pv;
                /* process GV */
                $path = $treeExpanded[$custId][Snap::A_PATH];
                $parents = $this->hlpTree->getParentsFromPathReversed($path);
                $gen = 1;
                foreach($parents as $parentId) {
                    if($gen > $gvMaxLevels) {
                        break;
                    }
                    if(!isset($result[$parentId])) {
                        $result[$parentId] = $this->_initEntry();
                        $result[$parentId][EntityQual::A_COMPRESS_ID] = $mapById[$parentId][Compress::A_ID];
                    }
                    $result[$parentId][EntityQual::A_GV] += $pv;
                    $gen++;
                }
                /* process PSAA */
                if(isset($mapTeams[$custId])) {
                    $psaa = 0;
                    $team = $mapTeams[$custId];
                    foreach($team as $memberId) {
                        $mPv = $result[$memberId][EntityQual::A_PV];
                        if($mPv > $psaaLevel) {
                            $psaa++;
                        }
                    }
                    $result[$custId][EntityQual::A_PSAA] = $psaa;
                }
            }
        }
        return $result;
    }
}