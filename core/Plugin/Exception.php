<?php

/**
 * DokuWiki WebDAV Plugin: Exception Plugin for Sabre DAV
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

namespace dokuwiki\plugin\webdav\core\Plugin;

use dokuwiki\plugin\webdav\core\Utils;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class Exception extends ServerPlugin
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
     * @param Exception|Error $e
     */
    public function logException($e)
    {
        if (!preg_match("/No 'Authorization: (Basic|Digest)' header found./", $e->getMessage())) {
            Utils::log('fatal', "[{class}] {message} in {file}({line})", [
                'class'   => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            Utils::log('debug', 'User-Agent: {agent}', ['agent' => @$_SERVER['HTTP_USER_AGENT']]);
            Utils::log('debug', 'Remote-User: {user}', ['user' => @$_SERVER['REMOTE_USER']]);
            Utils::log('debug', 'Request-URI: {uri}', ['uri' => @$_SERVER['REQUEST_URI']]);
            Utils::log('debug', 'Request-Method: {method}', ['method' => @$_SERVER['REQUEST_METHOD']]);
        }
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'exception';
    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    public function getPluginInfo()
    {
        return [
            'name'        => $this->getPluginName(),
            'description' => 'WebDAV plugin exception for DokuWiki',
            'link'        => 'https://dokuwiki.org/plugin:webdav',
        ];
    }
}
