<?php

/**
 * DokuWiki WebDAV Plugin - ODT File Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\core\DAV\Collection\ODT;

use dokuwiki\plugin\webdav\core\DAV\AbstractFile;
use Sabre\DAV\Exception\Forbidden;

class File extends AbstractFile
{

    /**
     * Return the rendered ODT document
     *
     * @todo Use the cache
     *
     * @return string
     */
    public function get()
    {
        if (auth_quickaclcheck($this->info['id']) < AUTH_READ) {
            throw new Forbidden('You are not allowed to access this file');
        }

        $odt      = plugin_load('renderer', 'odt_book');
        $filename = wikiFN($this->info['id']);
        $meta     = p_get_metadata($this->info['id']);

        $ID         = $page;
        $info       = [];
        $xmlcontent = p_render('odt_book', p_cached_instructions($filename, false, $page), $info);

        $odt->doc = $xmlcontent;
        $odt->setTitle(@$meta['title']);
        $odt->finalize_ODTfile();

        return $odt->doc;
    }

    public function getName()
    {
        return basename($this->info['file'], '.txt') . '.odt';
    }

    /**
     * Return the size of document
     *
     * @todo This is a workarund (always zero?)
     *
     * @return int
     */
    public function getSize()
    {
        return 0;
    }

    public function getContentType()
    {
        return 'application/vnd.oasis.opendocument.text';
    }
}
