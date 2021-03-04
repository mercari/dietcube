<?php

namespace Dietcube;

use Pimple\Container;

class Controller
{
    public const ACTION_NOT_FOUND = 'notFound';
    public const ACTION_METHOD_NOT_ALLOWED = 'methodNotAllowed';
    public const ACTION_INTERNAL_ERROR = 'internalError';

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $view_vars = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param mixed $key
     * @param null $value
     * @return $this
     */
    public function setVars($key, $value = null): self
    {
        if (is_array($key)) {
            $this->view_vars = array_merge($this->view_vars, $key);
        } else {
            $this->view_vars[$key] = $value;
        }

        return $this;
    }

    protected function isPost(): bool
    {
        if (stripos($this->container['global.server']->get('REQUEST_METHOD'), 'post') === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function get($name)
    {
        return $this->container[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    protected function query($name, $default = null)
    {
        return $this->container['global.get']->get($name, $default);
    }

    /**
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    protected function body($name = null, $default = null)
    {
        return $this->container['global.post']->get($name, $default);
    }

    /**
     * @param mixed $handler
     * @param array $data
     * @param array $query_params
     * @param bool $is_absolute
     * @return mixed
     */
    protected function generateUrl($handler, array $data = [], array $query_params = [], $is_absolute = false)
    {
        return $this->container['router']->url($handler, $data, $query_params, $is_absolute);
    }

    protected function findTemplate($name): string
    {
        return $name . $this->get('app')->getTemplateExt();
    }

    /**
     * @param $name
     * @param array $vars
     * @return mixed
     *
     * The Twig render returning type is `string`.
     * But we aren't determinable to the returning type because a user can change component by `get('app.renderer')`.
     */
    protected function render($name, array $vars = [])
    {
        $template = $this->findTemplate($name);

        return $this->get('app.renderer')->render($template, array_merge($this->view_vars, $vars));
    }

    /**
     * @param mixed $uri
     * @param int $code
     * @return void
     */
    protected function redirect($uri, $code = 302): void
    {
        $response = $this->getResponse();

        $response->setStatusCode($code);
        $response->setHeader('Location', $uri);
    }

    /**
     * @return mixed
     */
    protected function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Helper method to respond JSON.
     *
     * @param array $vars
     * @param string|null $charset
     * @return string JSON encoded string
     */
    protected function json(array $vars, $charset = 'utf-8')
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json;charset=' . $charset);

        return json_encode($vars);
    }
}
