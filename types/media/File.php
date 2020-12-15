<?php

/**
 * DokuWiki WebDAV Plugin - Media File Type
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\types\media;

use dokuwiki\plugin\webdav\core\AbstractFile;
use dokuwiki\plugin\webdav\core\Utils;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\UnsupportedMediaType;

class File extends AbstractFile
{
    public function delete()
    {
        Utils::log('debug', 'Delete media');

        if ($this->info['perm'] < AUTH_DELETE) {
            throw new Forbidden('You are not allowed to delete this file');
        }

        $res = media_delete($this->info['id'], $acl_check);

        // TODO remove metafile in attic ?
        //if ($metafile = $this->info['metafile']) {
        //    @unlink($metafile);
        //}

        if ($res == DOKU_MEDIA_DELETED) {
            return true;
        }

        if ($res == DOKU_MEDIA_INUSE) {
            throw new Forbidden('Media file in use');
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
            throw new Forbidden('Insufficient Permissions');
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

        Utils::log('debug', "Allowed files $regex");

        // check valid file type
        if (!preg_match('/\.(' . $regex . ')$/i', $this->info['path'])) {
            throw new UnsupportedMediaType($lang['uploadwrong']);
        }

        io_createNamespace($this->info['id'], 'media');

        if (!Utils::streamWriter($stream, $this->info['path'])) {
            throw new Forbidden($lang['uploadfail']);
        }

        // save the original filename
        io_saveFile($this->info['metafile'], serialize([
            'filename' => $this->info['filename'],
        ]));

        $timestamp_new = @filemtime($this->info['path']);
        $filesize_new  = filesize($this->info['path']);
        $sizechange    = $filesize_new - $filesize_old;

        if (!file_exists(mediaFN($this->info['id'], $timestamp_old)) && file_exists($this->info['path'])) {
            // add old revision to the attic if missing
            media_saveOldRevision($this->info['id']);
        }

        media_notify($this->info['id'], $this->info['path'], $this->getContentType(), $timestamp_old, $timestamp_new);

        // write entry in media log
        if ($overwrite) {
            addMediaLogEntry($timestamp_new, $this->info['id'], DOKU_CHANGE_TYPE_EDIT, '', '', null, $sizechange);
        } else {
            addMediaLogEntry($timestamp_new, $this->info['id'], DOKU_CHANGE_TYPE_CREATE, $lang['created'], '', null, $sizechange);
        }

        // TODO call MEDIA_UPLOAD_FINISH
    }
}
