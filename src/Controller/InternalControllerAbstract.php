<?php
/**
 *
 */

namespace Dietcube\Controller;

use Dietcube\Controller as BaseController;

abstract class InternalControllerAbstract extends BaseController
{
    protected function render($name, array $vars = [])
    {
        return $this->getInternalRenderer()->render($name . '.html.twig', $vars);
    }

    protected function getInternalRenderer()
    {
        $is_debug = $this->get('app.config')->get('debug', false);

        $loader = new \Twig_Loader_Filesystem($this->getInternalTemplateDir());
        $twig = new \Twig_Environment($loader, [
            'debug' => $is_debug,
            'cache' => false,
            'charset' => 'utf-8',
        ]);

        if ($is_debug) {
            $twig->addExtension(new \Twig_Extension_Debug());
        }

        return $twig;
    }

    protected function getInternalTemplateDir()
    {
        return dirname(__DIR__) . '/template';
    }
}
