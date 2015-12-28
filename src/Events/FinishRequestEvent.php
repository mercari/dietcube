<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;
use Symfony\Component\EventDispatcher\Event;

class FinishRequestEvent extends Event
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Application $app, Response $response)
    {
        $this->app = $app;
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
