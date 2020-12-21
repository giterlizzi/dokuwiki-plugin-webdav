<?php

/**
 * DokuWiki WebDAV Plugin - Tags Collection
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\types\tags;

use dokuwiki\plugin\webdav\core\AbstractDirectory;

class Directory extends AbstractDirectory
{
    const ROOT      = 'tags';
    const DIRECTORY = 'datadir';

    /** @inheritdoc */
    public function getChildren()
    {
        global $conf;

        $tag_helper = plugin_load('helper', 'tag');

        $children   = [];
        $data       = [];
        $class_type = $this->getClassType();

        if ($this->info['id']) {
            foreach ($tag_helper->getTopic('', null, $this->info['id']) as $tag) {
                $tag_id    = $tag['id'];
                $file_path = wikiFN($tag_id);
                $data[]    = [
                    'type'     => 'f',
                    'id'       => $tag_id,
                    'path'     => $file_path,
                    'ns'       => getNS($tag_id),
                    'size'     => filesize($file_path),
                    'mtime'    => filemtime($file_path),
                    'perm'     => auth_quickaclcheck($tag_id),
                    'hash'     => sha1_file($file_path),
                    'filename' => $tag_id . '.txt',
                    'dir'      => self::DIRECTORY,
                ];
            }
        } else {
            foreach ($tag_helper->tagOccurrences([], null, true) as $tag => $length) {
                $data[] = [
                    'type'    => 'd',
                    'dirname' => $tag,
                    'id'      => $tag,
                ];
            }
        }

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
