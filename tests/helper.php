<?php

namespace Dietcube;

use Pimple\Container;

// It should define delegation function only.
class DummyController extends Controller
{
    public function doRender(string $template_name, array $vars = []): string
    {
        return $this->render($template_name, $vars);
    }

    public function doFindTemplate(string $template_name): string
    {
        return $this->findTemplate($template_name);
    }
}

class DummyApplication extends Application
{
    public function config(Container $container)
    {
    }
}
