<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusLoyalty\Service\Calc\Sub;

use Praxigento\BonusBase\Repo\Data\Compress;
use Praxigento\BonusLoyalty\Repo\Entity\Data\Qualification as EntityQual;
use Praxigento\Downline\Repo\Entity\Data\Snap;
use Praxigento\Downline\Service\Map\Request\ById as DownlineMapByIdRequest;
use Praxigento\Downline\Service\Map\Request\TreeByDepth as DownlineMapTreeByDepthRequest;
use Praxigento\Downline\Service\Map\Request\TreeByTeams as DownlineMapTreeByTeamsRequest;
use Praxigento\Downline\Service\Snap\Request\ExpandMinimal as DownlineSnapExtendMinimalRequest;

class Qualification {

    /** @var  \Praxigento\Downline\Service\IMap */
    protected $_callDownlineMap;
    /** @var   \Praxigento\Downline\Service\ISnap */
    protected $_callDownlineSnap;
    /** @var \Praxigento\Downline\Api\Helper\Downline */
    protected $_toolDownlineTree;

    public function __construct(
        \Praxigento\Downline\Service\IMap $callDownlineMap,
        \Praxigento\Downline\Service\ISnap $callDownlineSnap,
        \Praxigento\Downline\Api\Helper\Downline $toolDownlineTree
    ) {
        $this->_callDownlineMap = $callDownlineMap;
        $this->_callDownlineSnap = $callDownlineSnap;
        $this->_toolDownlineTree = $toolDownlineTree;
    }

    private function _expandTree($data) {
        $req = new DownlineSnapExtendMinimalRequest();
        $req->setKeyCustomerId(Compress::ATTR_CUSTOMER_ID);
        $req->setKeyParentId(Compress::ATTR_PARENT_ID);
        $req->setTree($data);
        $resp = $this->_callDownlineSnap->expandMinimal($req);
        return $resp->getSnapData();
    }

    private function _initEntry() {
        $result = [
            EntityQual::ATTR_COMPRESS_ID => 0,
            EntityQual::ATTR_PV          => 0,
            EntityQual::ATTR_GV          => 0,
            EntityQual::ATTR_PSAA        => 0
        ];
        return $result;
    }

    private function _mapById($tree) {
        $req = new DownlineMapByIdRequest();
        $req->setDataToMap($tree);
        $req->setAsId(Compress::ATTR_CUSTOMER_ID);
        $resp = $this->_callDownlineMap->byId($req);
        return $resp->getMapped();
    }

    private function _mapByTeams($tree) {
        $req = new DownlineMapTreeByTeamsRequest();
        $req->setAsCustomerId(Compress::ATTR_CUSTOMER_ID);
        $req->setAsParentId(Compress::ATTR_PARENT_ID);
        $req->setDataToMap($tree);
        $resp = $this->_callDownlineMap->treeByTeams($req);
        return $resp->getMapped();
    }

    private function _mapByTreeDepthDesc($tree) {
        $req = new DownlineMapTreeByDepthRequest();
        $req->setDataToMap($tree);
        $req->setAsCustomerId(Compress::ATTR_CUSTOMER_ID);
        $req->setAsDepth(Snap::ATTR_DEPTH);
        $req->setShouldReversed(true);
        $resp = $this->_callDownlineMap->treeByDepth($req);
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
                    $result[$custId][EntityQual::ATTR_COMPRESS_ID] = $mapById[$custId][Compress::ATTR_ID];
                }
                /* process PV */
                $pv = $qData[$custId];
                $result[$custId][EntityQual::ATTR_PV] = $pv;
                /* process GV */
                $path = $treeExpanded[$custId][Snap::ATTR_PATH];
                $parents = $this->_toolDownlineTree->getParentsFromPathReversed($path);
                $gen = 1;
                foreach($parents as $parentId) {
                    if($gen > $gvMaxLevels) {
                        break;
                    }
                    if(!isset($result[$parentId])) {
                        $result[$parentId] = $this->_initEntry();
                        $result[$parentId][EntityQual::ATTR_COMPRESS_ID] = $mapById[$parentId][Compress::ATTR_ID];
                    }
                    $result[$parentId][EntityQual::ATTR_GV] += $pv;
                    $gen++;
                }
                /* process PSAA */
                if(isset($mapTeams[$custId])) {
                    $psaa = 0;
                    $team = $mapTeams[$custId];
                    foreach($team as $memberId) {
                        $mPv = $result[$memberId][EntityQual::ATTR_PV];
                        if($mPv > $psaaLevel) {
                            $psaa++;
                        }
                    }
                    $result[$custId][EntityQual::ATTR_PSAA] = $psaa;
                }
            }
        }
        return $result;
    }
}