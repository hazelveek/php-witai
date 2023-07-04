<?php
/**
 * Created by PhpStorm.
 * User: hazelcodes
 * Date: 7/3/23
 * Time: 2:27 PM
 */

namespace Hazelveek\PhpWitAi;


use Throwable;

class WitIntegrationException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}