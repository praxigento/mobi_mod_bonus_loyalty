<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Service\Calc\Request;
/**
 * @method string getDateApplied() Transaction applied dates for calculation. UTC current date is used if missed.
 * @method void setDateApplied(string $data)
 * @method string getDatePerformed() Operation performed dates for calculation. UTC current date is used if missed.
 * @method void setDatePerformed(string $data)
 */
class Bonus extends \Praxigento\Core\Service\Base\Request {

}