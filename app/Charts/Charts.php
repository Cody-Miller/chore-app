<?php

namespace App\Charts;

class Charts
{
    protected bool $hasData = false;

    public function hasData(): bool
    {
        return $this->hasData;
    }
}
