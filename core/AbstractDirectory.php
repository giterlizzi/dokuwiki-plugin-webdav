<?php

/**
 * DokuWiki WebDAV Collection Base Class
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\Collection;

class AbstractDirectory extends Collection
{
    const ROOT      = null;
    const DIRECTORY = null;

    public $info = [];

    /** @inheritdoc */
    public function __construct($info = [])
    {

        if (!static::ROOT || !static::DIRECTORY) {
            throw new \RuntimeException('Specify ROOT and DIRECTORY constant');
        }

        $this->info = $info;
    }

    /**
     * Return collection class type
     *
     * @return string
     */
    public function getClassType()
    {
        return substr(get_class($this), 0, strrpos(get_class($this), '\\'));
    }

    /** @inheritdoc */
    public function getName()
    {
        return (isset($this->info['dirname']) ? noNS($this->info['dirname']) : static::ROOT);
    }

    /** @inheritdoc */
    public function getLastModified()
    {
        return (isset($this->info['mtime']) ? $this->info['mtime'] : null);
    }

    /** @inheritdoc */
    public function getChildren()
    {
        global $conf;

        $children   = [];
        $data       = [];
        $dir        = str_replace(':', '/', (isset($this->info['id']) ? $this->info['id'] : ':'));
        $class_type = $this->getClassType();

        search($data, $conf[static::DIRECTORY], ['dokuwiki\plugin\webdav\core\Utils', 'searchCallback'], ['dir' => static::DIRECTORY], $dir);

        foreach ($data as $item) {
            if ($item['type'] == 'd') {
                $child_class = $class_type . '\\Directory';
            } else {
                $child_class = $class_type . '\\File';
            }

            $children[] = new $child_class($item);
        }

        return $children;
    }
}
