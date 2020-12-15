<?php

/**
 * DokuWiki plugin for Sabre DAV
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\Inode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class DokuWikiPlugin extends ServerPlugin
{

    const NS_DOKUWIKI = 'http://dokuwiki.org/ns';

    const DAV_ID_PROPERTY           = '{DAV:}id';
    const DAV_DISPLAYNAME_PROPERTY  = '{DAV:}displayname';
    const DAV_ISHIDDEN_PROPERTY     = '{DAV:}ishidden';
    const DAV_ISFOLDER_PROPERTY     = '{DAV:}isfolder';
    const DAV_ISCOLLECTION_PROPERTY = '{DAV:}iscollection';
    const DW_DESCRIPTION_PROPERTY   = '{http://dokuwiki.org/ns}description';
    const DW_TITLE_PROPERTY         = '{http://dokuwiki.org/ns}title';
    const DW_TAGS_PROPERTY          = '{http://dokuwiki.org/ns}tags';
    const DW_ID_PROPERTY            = '{http://dokuwiki.org/ns}id';

    /**
     * Initializes the plugin
     *
     * @param DAV\Server $server
     * @return void
     */
    public function initialize(Server $server)
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
        $info = $node->info;

        if ($info['type'] == 'd') {
            $propFind->handle(self::DAV_ISFOLDER_PROPERTY, 't');
            $propFind->handle(self::DAV_ISCOLLECTION_PROPERTY, '1');
        }

        if ($info['type'] == 'f') {
            $dw_id = $info['id'];
            $propFind->handle(self::DAV_ID_PROPERTY, $dw_id);
            $propFind->handle(self::DW_ID_PROPERTY, $dw_id);

            if ($info['dir'] == 'datadir') {
                $dw_meta = p_get_metadata($dw_id);
                $propFind->handle(self::DAV_DISPLAYNAME_PROPERTY, @$dw_meta['title']);
                $propFind->handle(self::DAV_ISHIDDEN_PROPERTY, (isHiddenPage($dw_id) ? '1' : '0'));
                $propFind->handle(self::DW_TITLE_PROPERTY, @$dw_meta['title']);
                $propFind->handle(self::DW_DESCRIPTION_PROPERTY, @$dw_meta['description']['abstract']);

                if (!plugin_isdisabled('tag')) {
                    $tag_helper = plugin_load('helper', 'tag');
                    $tags       = $tag_helper->_getSubjectMetadata($dw_id);
                    $propFind->handle(self::DW_TAGS_PROPERTY, join(',', $tags));
                }
            }
        }
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
