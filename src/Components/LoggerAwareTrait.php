<?php
/**
 *
 */

namespace Dietcube\Components;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
