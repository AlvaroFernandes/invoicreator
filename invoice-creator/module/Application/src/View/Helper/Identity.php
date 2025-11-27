<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Identity extends AbstractHelper
{
    /** @var callable */
    private $getIdentity;

    public function __construct(callable $getIdentity)
    {
        $this->getIdentity = $getIdentity;
    }

    public function __invoke()
    {
        $fn = $this->getIdentity;
        return $fn();
    }
}
