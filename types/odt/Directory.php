<?php

/**
 * DokuWiki WebDAV Plugin - ODT Directory Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\types\odt;

use dokuwiki\plugin\webdav\core;

class Directory extends core\Directory
{
    const ROOT      = 'odt';
    const DIRECTORY = 'datadir';
}
