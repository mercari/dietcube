<?php
/**
 *
 */

namespace Dietcube\Controller;

interface ErrorControllerInterface
{
    public function notFound(\Exception $error);

    public function methodNotAllowed(\Exception $error);

    public function internalError(\Exception $error);
}
