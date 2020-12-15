<?php

/**
 * DokuWiki WebDAV Plugin - Media Directory Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\types\media;

use dokuwiki\plugin\webdav\core\AbstractDirectory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

class Directory extends AbstractDirectory
{
    const ROOT      = 'media';
    const DIRECTORY = 'mediadir';

    public function createDirectory($name)
    {
        global $conf;

        if (auth_quickaclcheck($this->ns . ':*') < AUTH_CREATE) {
            throw new Forbidden('Insufficient Permissions');
        }

        // no dir hierarchies
        $sanitized_name = strtr($name, [
            ':' => $conf['sepchar'],
            '/' => $conf['sepchar'],
            ';' => $conf['sepchar'],
        ]);

        $id      = cleanID($this->info['ns'] . ':' . $sanitized_name);
        $fake_id = cleanID("$id:fake"); //add fake pageid

        io_createNamespace($fake_id, 'media');

        // save the original directory name
        io_saveFile(mediametaFN($id, '.dirname'), serialize([
            'dirname' => $name,
        ]));
    }

    public function delete()
    {
        $dir = dirname(mediaFN($this->info['id'] . ':fake'));

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
        $info = $this->info;

        $info['id']       = $this->info['id'] . ':' . cleanID($name);
        $info['path']     = mediaFN($info['id']);
        $info['perm']     = auth_quickaclcheck($info['id']);
        $info['type']     = 'f';
        $info['filename'] = $name;
        $info['metafile'] = mediametaFN($info['id'], '.filename');

        $file = new File($info);
        $file->put($data);
    }
}
