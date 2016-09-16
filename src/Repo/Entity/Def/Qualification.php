<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Repo\Entity\Def;

use Praxigento\BonusLoyalty\Data\Entity\Qualification as Entity;

class Qualification
    extends \Praxigento\Core\Repo\Def\Entity
    implements \Praxigento\BonusLoyalty\Repo\Entity\IQualification
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\Repo\IGeneric $repoGeneric
    ) {
        parent::__construct($resource, $repoGeneric, Entity::class);
    }

}