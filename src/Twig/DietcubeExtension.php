<?php
/**
 *
 */

namespace Dietcube\Twig;

use Dietcube\Components\ContainerAwareTrait;
use Pimple\Container;

class DietcubeExtension extends \Twig_Extension
{
    use ContainerAwareTrait;

    public function getName()
    {
        return 'dietcube';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('url', [$this, 'url']),
            new \Twig_SimpleFunction('absolute_url', [$this, 'absoluteUrl']),
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
        $url = $path = $router->url($handler, $data, $query_params);

        if ($is_absolute) {
            $url = $this->container['app']->getUrl() . $path;
        }

        return $url;
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
