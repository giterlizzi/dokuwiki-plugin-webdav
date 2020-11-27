<?php
/**
 * WebDAV Action Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2019-2020, Giuseppe Di Terlizzi
 */

class action_plugin_webdav extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        if (plugin_load('renderer', 'odt_book')) {
            $controller->register_hook('PLUGIN_WEBDAV_COLLECTIONS', 'BEFORE', $this, 'odtPlugin');
        }

        if ($this->getConf('show_button')) {
            $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenu');
        }

        $controller->register_hook('MEDIA_DELETE_FILE', 'AFTER', $this, 'deleteMeta');
    }

    public function odtPlugin(Doku_Event $event, $param)
    {
        $event->data['odt'] = new dokuwiki\plugin\webdav\types\odt\Directory();
    }

    public function deleteMeta(Doku_Event $event)
    {
        $id       = $event->data['id'];
        $metafile = mediametaFN($id, '.filename');

        if (@unlink($metafile)) {
            io_sweepNS($id, 'metadir');
        }
    }

    public function addMenu(Doku_Event $event)
    {
        if ($event->data['view'] != 'page') {
            return;
        }

        array_splice($event->data['items'], -1, 0, [new \dokuwiki\plugin\webdav\MenuItem]);
    }
}
