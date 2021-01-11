<?php

include dirname(__FILE__) . '/../vendor/autoload.php';
include dirname(__FILE__) . '/lib/DAVServerTest.php';

use Sabre\HTTP;

/**
 * DAV tests for the webdav plugin
 *
 * @group plugin_webdav
 * @group plugins
 */
class webdav_plugin_webdav_test extends DokuWikiTest
{
    public function setUp()
    {
        $this->server = new DAVServerTest;
    }

    public function testPropfindMethodStatus()
    {
        $response = $this->server->request(new HTTP\Request('PROPFIND', '/wiki'));
        $this->assertEquals(207, $response->getStatus());
    }

    public function testGetMethodStatus()
    {
        $response = $this->server->request(new HTTP\Request('GET', '/wiki/pages/wiki/dokuwiki.txt'));
        $this->assertEquals(200, $response->getStatus());
    }

    public function testNotFoundStatus()
    {
        $response = $this->server->request(new HTTP\Request('GET', '/wiki/pages/wiki/foo.txt'));
        $this->assertEquals(404, $response->getStatus());
    }

    public function testProperties()
    {
        $propfind = <<<EOL
<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:">
    <d:prop xmlns:dw="http://dokuwiki.org/ns">
        <d:creationdate/>
        <d:getlastmodified/>
        <d:getcontentlength/>
        <d:getcontenttype/>
        <d:resourcetype/>
        <d:getetag/>
        <d:displayname/>
        <dw:id/>
        <dw:title/>
        <dw:description/>
    </d:prop>
</d:propfind>
EOL;

        $request = new HTTP\Request('PROPFIND', '/wiki/pages/wiki/dokuwiki.txt');
        $request->setBody($propfind);
        $response = $this->server->request($request);

        $body = $response->getBodyAsString();

        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('dw', 'http://dokuwiki.org/ns');

        $paths = [
            '/d:multistatus/d:response/d:href',
            '/d:multistatus/d:response/d:propstat/d:prop/d:creationdate',
            '/d:multistatus/d:response/d:propstat/d:prop/d:getlastmodified',
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength',
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname',
            '/d:multistatus/d:response/d:propstat/d:prop/dw:id',
            '/d:multistatus/d:response/d:propstat/d:prop/dw:title',
            '/d:multistatus/d:response/d:propstat/d:prop/dw:description',
        ];

        $this->assertEquals(207, $response->status);
        $this->assertTrue(strpos($body, 'http://dokuwiki.org/ns') !== false, $body);

        foreach ($paths as $path) {
            $result = $xml->xpath($path);
            $this->assertCount(1, $result, "XPath query $path");
        }

    }
}
