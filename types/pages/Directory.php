<?php

/**
 * DokuWiki WebDAV Plugin - Pages Directory Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\types\pages;

use dokuwiki\plugin\webdav;
use dokuwiki\plugin\webdav\core\Utils;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

class Directory extends webdav\core\AbstractDirectory
{
    const ROOT      = 'pages';
    const DIRECTORY = 'datadir';

    public function createDirectory($name)
    {
        global $conf;

        Utils::log('debug', "Create directory $name");

        if (auth_quickaclcheck($this->ns . ':*') < AUTH_CREATE) {
            throw new Forbidden('Insufficient Permissions');
        }

        // no dir hierarchies
        $name = strtr($name, [
            ':' => $conf['sepchar'],
            '/' => $conf['sepchar'],
            ';' => $conf['sepchar'],
        ]);

        $name = cleanID($this->info['ns'] . ':' . $name . ':fake'); //add fake pageid

        io_createNamespace($name, 'pages');
    }

    public function delete()
    {
        $dir = dirname(wikiFN($this->info['ns'] . ':fake'));

        Utils::log('debug', "Delete directory");

        if (@!file_exists($dir)) {
            throw new NotFound('Directory does not exist');
        }

        $files = glob("$dir/*");

        if (count($files)) {
            throw new Forbidden('Directory not empty');
        }

        if (!rmdir($dir)) {
            throw new Forbidden('Failed to delete directory');
        }
    }

    public function createFile($name, $data = null)
    {
        $id = $this->info['ns'] . ':' . preg_replace('#\.txt$#', '', cleanID($name));
        Utils::log('debug', "Create page $id - $name");

        Utils::saveWikiText($id, $data, 'create');
    }
}
