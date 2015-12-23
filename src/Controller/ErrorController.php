<?php
/**
 *
 */

namespace Dietcube\Controller;

use Dietcube\Controller;

class ErrorController extends Controller implements ErrorControllerInterface
{
    public function notFound()
    {
        $this->getResponse()->setStatusCode(404);
        return $this->render('error404');
    }

    public function methodNotAllowed()
    {
        $this->getResponse()->setStatusCode(403);
        return $this->render('error403');
    }

    public function internalError(\Exception $error)
    {
        $this->getResponse()->setStatusCode(500);
        return $this->render('error500', ['error' => $error]);
    }
}
