<?php

use Sabre\DAV;
use Sabre\HTTP;
use dokuwiki\plugin\webdav\core\DAV\Collection;
use dokuwiki\plugin\webdav\core\Plugin;

class DAVServerTest
{

    public $server;
    public $output_buffer;

    public function __construct()
    {
        $wiki_collections = [
            'pages' => new Collection\Pages\Directory(),
            'media' => new Collection\Media\Directory(),
        ];

        $this->server = new DAV\Server(new DAV\SimpleCollection('root', [
            new DAV\SimpleCollection('wiki', $wiki_collections),
        ]));

        $this->server->setBaseUri('/');
        $this->server->addPlugin(new Plugin\DokuWiki());
    }

    /**
     * Callback for ob_start
     *
     * This continues to fill our own buffer, even when some part
     * of the code askes for flushing the buffers
     *
     * @param string $buffer
     */
    public function ob_start_callback($buffer)
    {
        $this->output_buffer .= $buffer;
    }

    /**
     * Makes a request, and returns a response object.
     *
     * You can either pass an instance of Sabre\HTTP\Request, or an array,
     * which will then be used as the _SERVER array.
     *
     * @param array|\Sabre\HTTP\Request $request
     * @return \Sabre\HTTP\Response
     */
    public function request($request)
    {
        if (is_array($request)) {
            $request = HTTP\Sapi::createFromServerArray($request);
        }
        $response = new ResponseMock();

        $this->server->httpRequest  = $request;
        $this->server->httpResponse = $response;

        # Capture stdout response
        ob_start([$this, 'ob_start_callback']);
        $this->server->exec();
        ob_end_flush();

        return $this->server->httpResponse;
    }
}

class ResponseMock extends HTTP\Response
{
    /**
     * Making these public.
     */
    public $body;
    public $status;
}
