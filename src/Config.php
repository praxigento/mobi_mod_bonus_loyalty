<?php
/**
 * Module's configuration (hard-coded).
 *
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty;

use Praxigento\Wallet\Lib\Config as CfgWallet;

class Config extends \Praxigento\BonusBase\Config
{
    /**
     * Decorated values.
     */
    const CODE_TYPE_ASSET_WALLET_ACTIVE = CfgWallet::CODE_TYPE_ASSET_WALLET_ACTIVE;
    /**
     * This module's calculation types.
     */
    const CODE_TYPE_CALC_BONUS = 'LOYALTY_BONUS';
    const CODE_TYPE_CALC_COMPRESSION = 'LOYALTY_BON_COMPRESS';
    const CODE_TYPE_CALC_QUALIFICATION = 'LOYALTY_BON_QUAL';

    /**
     * This module's operation types.
     */
    const CODE_TYPE_OPER_BONUS_LOYALTY = 'LOYALTY_BONUS';
    const MODULE = 'Praxigento_BonusLoyalty';
}