<?php

/**
 * DokuWiki WebDAV Plugin - Pages File Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\core\DAV\Collection\Pages;

use dokuwiki\plugin\webdav\core\DAV\AbstractFile;
use dokuwiki\plugin\webdav\core\Utils;

class File extends AbstractFile
{
    public function delete()
    {
        Utils::log('debug', "Delete page");
        Utils::saveWikiText($this->info['id'], null, 'delete');
    }

    public function put($data)
    {
        Utils::log('debug', "Edit page");
        Utils::saveWikiText($this->info['id'], Utils::streamReader($data), 'edit');
    }
}
