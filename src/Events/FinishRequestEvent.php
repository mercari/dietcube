<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;

class FinishRequestEvent extends DietcubeEventAbstract
{
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
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param Response $response
     * @return FinishRequestEvent
     */
    public function setResponse(Response $response): self
    {
        $this->response = $response;
        return $this;
    }
}
