<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Exception;

use Throwable;

class TacvTypeNotSupportedException extends \Exception
{
    public function __construct($type = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Type %s not supported', $type), $code, $previous);
    }
}
