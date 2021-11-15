<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Exception;

class TacvCallException extends \Exception
{
    protected $message = 'api.error.green-pass.tacv-unreachable';
}
