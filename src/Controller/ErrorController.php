<?php
/**
 *
 */

namespace Dietcube\Controller;

use Dietcube\Controller as BaseController;

class ErrorController extends BaseController
{
    public function notFound()
    {
        return null;
    }

    public function methodNotAllowed()
    {
        return null;
    }

    public function internalError(\Exception $error)
    {
        return null;
    }
}
