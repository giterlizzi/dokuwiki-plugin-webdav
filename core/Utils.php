<?php

/**
 * DokuWiki WebDAV Plugin: Util Class
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV\Exception\Forbidden;

class Utils
{
    /**
     * Read from stream
     *
     * @param resource $stream resource
     *
     * @return string
     */
    public static function streamReader($stream)
    {
        $data = '';

        while (($buf = fread($stream, 8192)) != '') {
            $data .= $buf;
        }
        return $data;
    }

    /**
     * Write a file
     *
     * @param resource $stream resource
     * @param string   $file   file path
     *
     * @return void
     */
    public static function streamWriter($stream, $file)
    {
        global $conf;

        $fileexists = @file_exists($file);

        io_makeFileDir($file);
        io_lock($file);

        $fh = @fopen($file, 'wb');

        if (!$fh) {
            io_unlock($file);
            return false;
        }

        while (($buf = fread($stream, 8192)) != '') {
            fwrite($fh, $buf);
        }

        fclose($fh);

        if (!$fileexists && !empty($conf['fperm'])) {
            chmod($file, $conf['fperm']);
        }

        io_unlock($file);

        return true;
    }

    /**
     * Save Wiki Text
     *
     * @throws DAV\Exception\Forbidden
     *
     * @param string $id
     * @param string $text
     * @param string $mode
     *
     * @return void
     */
    public static function saveWikiText($id, $text, $mode = 'edit')
    {
        // Add 2 return chars in "create" mode and "zero" byte size
        if ($mode == 'create' && strlen($text) == 0) {
            $text = "\n\n";
        }

        self::log('debug', 'Save content of {id} page ({size} bytes - {mode} mode)', [
            'id'   => $id,
            'size' => strlen($text),
            'mode' => $mode,
        ]);

        $auth_permission = AUTH_EDIT;

        if ($mode == 'create') {
            $auth_permission = AUTH_CREATE;
        }

        // check ACL permissions
        if (auth_quickaclcheck($id) < $auth_permission) {
            throw new Forbidden('Insufficient Permissions');
        }

        if (!utf8_check($text)) {
            throw new Forbidden('Seems not to be valid UTF-8 text');
        }

        switch ($mode) {
            case 'create':
                $summary = 'Created via WebDAV';
                break;
            case 'delete':
                $summary = 'Deleted via WebDAV';
                break;
            case 'edit':
            default:
                $summary = 'Edited via WebDAV';
                break;
        }

        saveWikiText($id, $text, $summary, false);
    }

    /**
     * Search callback
     *
     * @param array   $data
     * @param string  $base
     * @param string  $file
     * @param string  $type
     * @param integer $lvl
     * @param array   $opts
     *
     * @return array
     */
    public static function searchCallback(&$data, $base, $file, $type, $lvl, $opts = [])
    {
        $item = [];

        if (!isset($opts['dir'])) {
            $opts['dir'] = 'datadir';
        }

        $is_dir      = ($type == 'd');
        $is_mediadir = ($opts['dir'] == 'mediadir');

        $item['id']       = pathID($file, $is_dir);
        $item['type']     = $type;
        $item['dir']      = $opts['dir'];
        $item['metafile'] = null;
        $item['metadir']  = null;

        if ($is_dir) {
            $item['perm']    = auth_quickaclcheck($item['id'] . ':*');
            $item['ns']      = $item['id'];
            $item['mtime']   = filemtime("$base/$file");
            $item['dirname'] = $item['ns'];
        } else {
            $item['path']      = ($is_mediadir) ? mediaFN($item['id']) : wikiFN($item['id']);
            $item['mime_type'] = ($is_mediadir) ? mime_content_type($item['path']) : null;
            $item['perm']      = auth_quickaclcheck($item['id']);
            $item['ns']        = getNS($item['id']);
            $item['size']      = filesize($item['path']);
            $item['mtime']     = filemtime($item['path']);
            $item['hash']      = sha1_file($item['path']);
            $item['file']      = basename($file);
            $item['filename']  = $item['file'];
            $item['dirname']   = $item['ns'];
        }

        /**
         * Use mediameta for fetch original directory and file name:
         *
         *   <ID>.filename    array ( filename => 'Original File Name' )
         *   <NS>.dirname     array ( dirname  => 'Original Directory Name' )
         */
        if ($is_mediadir) {
            $metafile = mediametaFN($item['id'], '.filename');
            $metadir  = mediametaFN($item['ns'], '.dirname');

            if (file_exists($metafile) && $meta = unserialize(io_readFile($metafile, false))) {
                $item['metafile'] = $metafile;
                $item['filename'] = $meta['filename'];
            }

            if (file_exists($metadir) && $meta = unserialize(io_readFile($metadir, false))) {
                $item['metadir'] = $metadir;
                $item['dirname'] = $meta['dirname'];
            }
        }

        if ($item['perm'] < AUTH_READ) {
            return false;
        }

        $data[] = $item;
        return false;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    public static function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Log in DokuWiki debug log
     *
     * @see dbglog()
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public static function log($level, $message, $context = [])
    {
        // "{category} [{user}] [{ip}] [{level}] {message}"

        dbglog(self::interpolate("{category} {user} [{level}] {message}", [
            'category' => 'WebDAV',
            'user'     => (isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : '-'),
            'ip'       => clientIP(),
            'level'    => str_pad(strtoupper($level), 5),
            'message'  => self::interpolate($message, $context),
        ]));

        if (isset($context['exception'])) {
            self::log('error', $context['exception']->getMessage());
        }
    }
}
