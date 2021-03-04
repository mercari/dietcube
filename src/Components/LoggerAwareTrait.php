<?php

namespace Dietcube\Components;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    /** @var LoggerInterface|null */
    protected $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
