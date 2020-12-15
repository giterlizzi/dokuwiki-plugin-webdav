<?php

/**
 * DokuWiki WebDAV Plugin: Exception Plugin for Sabre DAV
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class ExceptionPlugin extends ServerPlugin
{
    /**
     * This initializes the plugin.
     *
     * This function is called by \Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param \Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(Server $server)
    {
        $server->on('exception', [$this, 'logException'], 10);
    }

    /**
     * Log exception
     *
     */
    public function logException($ex)
    {
        Utils::log('fatal', "[{class}] {message} in {file}({line})", [
            'class'   => get_class($ex),
            'message' => $ex->getMessage(),
            'file'    => $ex->getFile(),
            'line'    => $ex->getLine(),
        ]);

        // foreach (explode("\n", $ex->getTraceAsString()) as $trace) {
        //     Utils::log('fatal', "[{class}]\t{trace}", [
        //         'class' => get_class($ex),
        //         'trace' => $trace,
        //     ]);
        // }
    }
}
