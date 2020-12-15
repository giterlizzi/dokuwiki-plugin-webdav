<?php

namespace dokuwiki\plugin\webdav\core;

/**
 * DokuWiki WebDAV Plugin: Lock file backend for Sabre DAV
 *
 * @copyright Copyright (C) 2019-2020
 * @author Giuseppe Di Terlizzi (giuseppe.diterlizzi@gmail.com)
 * @license GNU GPL 2
 */

use Sabre\DAV;

class LocksFileBackend extends DAV\Locks\Backend\File
{
    /**
     * Locks a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri, DAV\Locks\LockInfo $lockInfo)
    {
        $lockInfo->user = @$_SERVER['REMOTE_USER'];
        $lockInfo->ua   = @$_SERVER['HTTP_USER_AGENT'];
        return parent::lock($uri, $lockInfo);
    }
}
