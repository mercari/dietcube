<?php
/**
 *
 */

namespace Dietcube\Events;

final class DietcubeEvents
{
    const BOOT = 'dietcube.boot';

    const ROUTING = 'dietcube.routing';

    const EXECUTE_ACTION = 'dietcube.execute_action';

    const FILTER_RESPONSE = 'dietcube.filter_response';

    const FINISH_REQUEST = 'dietcube.finish_request';
}
