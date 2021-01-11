<?php

/**
 * DokuWiki WebDAV Plugin - Tags File Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\core\DAV\Collection\Tags;

use dokuwiki\plugin\webdav\core\DAV\AbstractFile;

class File extends AbstractFile
{
    public function getName()
    {
        // Windows users: Replace NS separator with "U+A789 ꞉ MODIFIER LETTER COLON"
        if (preg_match('/(WebDAVFS|OneNote|Microsoft-WebDAV)/', $_SERVER['HTTP_USER_AGENT'])) {
            return str_replace(':', "\xea\x9e\x89", $this->info['filename']);
        }
        return $this->info['filename'];
    }
}
