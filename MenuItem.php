<?php

/**
 * DokuWiki WebDAV Menu Item
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav;

use dokuwiki\Menu\Item\AbstractItem;

/**
 * Class MenuItem
 *
 * Implements the WebDAV button for DokuWiki's menu system
 *
 * @package dokuwiki\plugin\webdav
 */
class MenuItem extends AbstractItem
{

    /** @var string icon file */
    protected $svg = __DIR__ . '/folder-network-outline.svg';

    /** @var string do action for this plugin */
    protected $type = 'webdav';

    public function getLinkAttributes($classprefix = 'menuitem ')
    {
        $attr = parent::getLinkAttributes($classprefix);

        if (empty($attr['class'])) {
            $attr['class'] = '';
        }

        $attr['class'] .= ' plugin_webdav ';
        $attr['data-webdav-url'] = getBaseURL(true) . 'lib/plugins/webdav/server.php/wiki/';

        return $attr;
    }

    /**
     * Get label from plugin language file
     *
     * @return string
     */
    public function getLabel()
    {
        return 'WebDAV';
    }

    public function getLink()
    {
        return 'javascript:void(0)';
    }
}
