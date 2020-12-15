<?php

/**
 * DokuWiki WebDAV Plugin: Dummy GET Response Plugin
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class DummyGetResponsePlugin extends ServerPlugin
{
    /** @var \Sabre\DAV\Server */
    protected $server;

    /**
     * @param \Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(Server $server)
    {
        $server->on('method:GET', [$this, 'httpGet'], 200);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return false
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        $string = 'This is the WebDAV interface. It can only be accessed by WebDAV clients.';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $string);
        rewind($stream);

        $response->setStatus(200);
        $response->setBody($stream);

        return false;
    }
}
