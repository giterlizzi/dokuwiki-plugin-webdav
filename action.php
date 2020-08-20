<?php
/**
 * WebDAV Action Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2019-2020, Giuseppe Di Terlizzi
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class action_plugin_webdav extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        if (plugin_load('renderer', 'odt_book')) {
            $controller->register_hook('PLUGIN_WEBDAV_COLLECTIONS', 'BEFORE', $this, 'odt_plugin');
        }
    }

    public function odt_plugin(Doku_Event $event, $param)
    {
        $event->data['odt'] = new dokuwiki\plugin\webdav\types\odt\Directory();
    }
}
