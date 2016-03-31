<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Bonus\Loyalty\Lib\Service;

use Praxigento\Bonus\Loyalty\Lib\Service\Calc\Request;
use Praxigento\Bonus\Loyalty\Lib\Service\Calc\Response;

interface ICalc {

    /**
     * @param Request\Bonus $req
     *
     * @return Response\Bonus
     */
    public function bonus(Request\Bonus $req);

    /**
     * @param Request\Compress $req
     *
     * @return Response\Compress
     */
    public function compress(Request\Compress $req);

    /**
     * @param Request\Qualification $req
     *
     * @return Response\Qualification
     */
    public function qualification(Request\Qualification $req);

}