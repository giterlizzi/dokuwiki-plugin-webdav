<?php

/**
 * DokuWiki WebDAV Plugin - Tags File Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\types\tags;

use dokuwiki\plugin\webdav\core\AbstractFile;

class File extends AbstractFile
{
    public function getName()
    {
        // No ":" NS separator for Windows User
        if (preg_match('/(WebDAVFS|OneNote|Microsoft-WebDAV)/', $_SERVER['HTTP_USER_AGENT'])) {
            return str_replace(':', '.', $this->info['filename']);
        }
        return $this->info['filename'];
    }
}
