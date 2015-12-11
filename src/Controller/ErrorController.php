<?php
/**
 *
 */

namespace Dietcube\Controller;

class ErrorController extends InternalControllerAbstract
{
    public function notFound()
    {
        return $this->render('error404');
    }

    public function methodNotAllowed()
    {
        return $this->render('error403');
    }

    public function internalError(\Exception $error)
    {
        $this->setHeader('HTTP', 500, $replace = true);
        return $this->render('error500', ['error' => $error]);
    }
}
