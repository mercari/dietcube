<?php
/**
 *
 */

namespace Dietcube\Twig;

use Dietcube\Components\ContainerAwareTrait;
use Pimple\Container;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DietcubeExtension extends AbstractExtension
{
    use ContainerAwareTrait;

    public function getName()
    {
        return 'dietcube';
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('url', [$this, 'url']),
            new TwigFunction('absolute_url', [$this, 'absoluteUrl']),
        ];
    }

    /**
     * This method is for Router::url().
     *
     * @param string $handler
     * @param array $data
     * @param array $query_params
     * @param bool $is_absolute
     * @return string url
     */
    public function url($handler, array $data = [], array $query_params = [], $is_absolute = false)
    {
        $router = $this->container['router'];
        return $router->url($handler, $data, $query_params, $is_absolute);
    }

    /**
     * This method is the shortcut for Router::url() with true of is_absolute flag.
     *
     * @param string $handler
     * @param array $data
     * @param array $query_params
     * @return string url
     */
    public function absoluteUrl($handler, array $data = [], array $query_params = [])
    {
        return $this->url($handler, $data, $query_params, true);
    }
}
