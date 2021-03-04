<?php
/**
 *
 */

namespace Dietcube\Events;

final class DietcubeEvents
{
    public const BOOT = 'dietcube.boot';

    public const ROUTING = 'dietcube.routing';

    public const EXECUTE_ACTION = 'dietcube.execute_action';

    public const FILTER_RESPONSE = 'dietcube.filter_response';

    public const FINISH_REQUEST = 'dietcube.finish_request';
}
