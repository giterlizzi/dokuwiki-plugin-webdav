<?php

/**
 * DokuWiki plugin for Sabre DAV
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV;
use Sabre\DAV\Inode;
use Sabre\DAV\PropFind;

class Plugin extends DAV\ServerPlugin
{

    const NS_DOKUWIKI = 'https://dokuwiki.org/ns';

    /**
     * Initializes the plugin
     *
     * @param DAV\Server $server
     * @return void
     */
    public function initialize(DAV\Server $server)
    {
        $server->xml->namespaceMap[self::NS_DOKUWIKI] = 'dw';
        $server->on('propFind', [$this, 'propFind']);
    }

    /**
     * Our PROPFIND handler
     *
     * @param PropFind $propFind
     * @param INode $node
     * @return void
     */
    public function propFind(PropFind $propFind, INode $node)
    {

        $path    = $propFind->getPath();
        $page_id = $this->getPageID($path);
        $meta    = $this->getMeta($page_id);

        if (preg_match('/^pages\//', $path)) {
            $propFind->handle('{DAV:}displayname', @$meta['title']);
            $propFind->handle('{' . self::NS_DOKUWIKI . '}id', $page_id);
            $propFind->handle('{' . self::NS_DOKUWIKI . '}title', @$meta['title']);
            $propFind->handle('{' . self::NS_DOKUWIKI . '}description', @$meta['description']['abstract']);

            if (!plugin_isdisabled('tag')) {
                $tag_helper = plugin_load('helper', 'tag');
                $tags       = $tag_helper->_getSubjectMetadata($page_id);
                $propFind->handle('{' . self::NS_DOKUWIKI . '}tags', join(',', $tags));
            }
        }
    }

    public function getPageID($path)
    {
        return cleanID(str_replace(['pages/', '/', '.txt'], ['', ':', ''], $path));
    }

    public function getMeta($page_id)
    {
        return p_get_metadata($page_id);
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
            'description' => 'WebDAV plugin helper for DokuWiki',
            'link'        => 'https://dokuwiki.org/plugin:webdav',
        ];
    }
}
