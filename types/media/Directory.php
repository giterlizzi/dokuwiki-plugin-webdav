<?php

namespace dokuwiki\plugin\webdav\types\media;

use dokuwiki\plugin\webdav\core;

class Directory extends core\Directory
{
    const ROOT      = 'media';
    const DIRECTORY = 'mediadir';

    public function createDirectory($name)
    {
        global $conf;

        if (auth_quickaclcheck($this->ns . ':*') < AUTH_CREATE) {
            throw new DAV\Exception\Forbidden('Insufficient Permissions');
        }

        // no dir hierarchies
        $name = strtr($name, array(
            ':' => $conf['sepchar'],
            '/' => $conf['sepchar'],
            ';' => $conf['sepchar'],
        ));

        $name = cleanID($this->ns . ':' . $name . ':fake'); //add fake pageid

        io_createNamespace($name, 'media');
    }

    public function delete()
    {
        $dir = dirname(mediaFN($this->info['id'] . ':fake'));

        if (@!file_exists($dir)) {
            throw new DAV\Exception\NotFound('Directory does not exist');
        }

        $files = glob("$dir/*");

        if (count($files)) {
            throw new DAV\Exception\Forbidden('Directory not empty');
        }

        if (!rmdir($dir)) {
            throw new DAV\Exception\Forbidden('Failed to delete directory');
        }
    }

    public function createFile($name, $data = null)
    {
        $file = new MediaFile($this->info['id'] . ":$name");
        $file->put($data);
    }
}
