<?php

namespace dokuwiki\plugin\webdav\types\media;

use Sabre\DAV;
use dokuwiki\plugin\webdav\core;

class File extends core\File
{
    public function delete()
    {
        core\Utils::log('debug', 'Delete media');

        if ($this->info['perm'] < AUTH_DELETE) {
            throw new DAV\Exception\Forbidden('You are not allowed to delete this file');
        }

        $res = media_delete($this->info['id'], $acl_check);

        if ($res == DOKU_MEDIA_DELETED) {
            return true;
        }

        if ($res == DOKU_MEDIA_INUSE) {
            throw new DAV\Exception\Forbidden('Media file in use');
        }
    }

    public function put($stream)
    {
        global $lang;

        // check ACL permissions
        if (@file_exists($this->info['path'])) {
            $perm_needed = AUTH_DELETE;
        } else {
            $perm_needed = AUTH_UPLOAD;
        }

        if ($this->info['perm'] < $perm_needed) {
            throw new DAV\Exception\Forbidden('Insufficient Permissions');
        }

        $overwrite     = file_exists($this->info['path']);
        $filesize_old  = $overwrite ? filesize($this->info['path']) : 0;
        $timestamp_old = @filemtime($this->info['path']);

        // get filetype for regexp check
        $types = array_keys(getMimeTypes());
        $types = array_map(
            function ($q) {
                return preg_quote($q, "/");
            },
            $types
        );
        $regex = join('|', $types);

        core\Utils::log('debug', "Allowed files $regex");

        // check valid file type
        if (!preg_match('/\.(' . $regex . ')$/i', $this->info['path'])) {
            throw new DAV\Exception\Forbidden($lang['uploadwrong']);
        }

        io_createNamespace($this->info['id'], 'media');

        if (!$this->streamWriter($stream, $this->info['path'])) {
            throw new DAV\Exception\Forbidden($lang['uploadfail']);
        }

        $timestamp_new = @filemtime($this->info['path']);
        $filesize_new  = filesize($this->info['path']);
        $sizechange    = $filesize_new - $filesize_old;

        media_notify($this->info['id'], $this->info['path'], $this->getContentType(), $timestamp_old);

        // write entry in media log
        if ($overwrite) {
            addMediaLogEntry($timestamp_new, $this->info['id'], DOKU_CHANGE_TYPE_EDIT, '', '', null, $sizechange);
        } else {
            addMediaLogEntry($timestamp_new, $this->info['id'], DOKU_CHANGE_TYPE_CREATE, $lang['created'], '', null, $sizechange);
        }

        // TODO call MEDIA_UPLOAD_FINISH
    }
}
