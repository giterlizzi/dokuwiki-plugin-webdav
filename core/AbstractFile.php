<?php

/**
 * DokuWiki WebDAV File Base Class
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\File;

/**
 * Base class
 */
class AbstractFile extends File
{

    public $info = [];

    /**
     * DokuWiki File base class
     *
     * @param string $id Page $ID
     */
    public function __construct($info)
    {
        $this->info = $info;
    }

    /**
     * Check the DokuWiki ACL and returns the data.
     *
     * @throws DAV\Exception\Forbidden
     * @return mixed
     */
    public function get()
    {
        if ($this->info['perm'] < AUTH_READ) {
            throw new DAV\Exception\Forbidden('You are not allowed to access this file');
        }

        return fopen($this->info['path'], 'rb');
    }

    /**
     * Returns the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return $this->info['filename'];
    }

    /**
     * Renames the node
     *
     * @todo Implement or use Move Plugin
     *
     * @param string $name The new name
     *
     * @throws DAV\Exception\Forbidden
     * @return void
     */
    public function setName($name)
    {
        throw new DAV\Exception\Forbidden('Permission denied to rename file');
    }

    /**
     * Returns the size of the file, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->info['size'];
    }

    /**
     * Returns the ETag for a file.
     *
     * @return string
     */
    public function getETag()
    {
        return '"' . $this->info['hash'] . '"';
    }

    /**
     * Returns the last modification time as a unix timestamp.
     *
     * @return int
     */
    public function getLastModified()
    {
        return $this->info['mtime'];
    }

    /**
     * Returns the mime-type for a file.
     *
     * @return string
     */
    public function getContentType()
    {
        return @$this->info['mime_type'];
    }
}
