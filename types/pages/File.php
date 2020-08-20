<?php

namespace dokuwiki\plugin\webdav\types\pages;

use dokuwiki\plugin\webdav\core;

class File extends core\File
{
    public function delete()
    {
        core\Utils::log('debug', "Delete page");
        core\Utils::saveWikiText($this->info['id'], null, 'delete');
    }

    public function put($data)
    {
        core\Utils::log('debug', "Edit page");
        core\Utils::saveWikiText($this->info['id'], core\Utils::streamReader($data), 'edit');
    }
}
