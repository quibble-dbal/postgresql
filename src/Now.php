<?php

namespace Quibble\Postgresql;

use Quibble\Dabble;

class Now extends Dabble\Now
{
    public function __construct()
    {
        return parent::__construct('NOW()');
    }
}

