<?php
/**
 *
 */

namespace Dietcube\Controller;

class DebugController extends InternalControllerAbstract
{
    public function dumpErrors(\Exception $errors)
    {
        return $this->render('debug', [
            'error_class_name' => get_class($errors),
            'errors' => $errors,
            'error_trace' => preg_replace(
                ['!' . $this->get('app')->getAppRoot() . '!',],
                ['#root'],
                $errors->getTraceAsString()
            ),
            'get_params'    => $this->get('global.get')->get(),
            'post_params'   => $this->get('global.post')->get(),
            'cookie_params' => $this->get('global.cookie')->get(),
            'server_params' => $this->get('global.server')->get(),
        ]);
    }
}

