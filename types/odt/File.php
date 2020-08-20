<?php

namespace dokuwiki\plugin\webdav\types\odt;

use Sabre\DAV;
use dokuwiki\plugin\webdav\core;

class File extends core\File
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
            throw new DAV\Exception\Forbidden('You are not allowed to access this file');
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
