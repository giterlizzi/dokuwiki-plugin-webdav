<?php

/**
 * DokuWiki WebDAV Plugin Endpoint
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

# NOTE Some Linux distributon change the location of DokuWiki core libraries (DOKU_INC)
#
#      Bitnami (Docker)         /opt/bitnami/dokuwiki
#      LinuxServer.io (Docker)  /app/dokuwiki
#      Arch Linux               /usr/share/webapps/dokuwiki
#      Debian/Ubuntu            /usr/share/dokuwiki
#
# NOTE If DokuWiki core libraries (DOKU_INC) is in another location you can
#      create a PHP file in this directory called "doku_inc.php" with
#      this content:
#
#           <?php define('DOKU_INC', '/path/dokuwiki/');
#
#      (!) This file may be deleted on every upgrade of plugin

$doku_inc_dirs = [
    '/opt/bitnami/dokuwiki',
    '/usr/share/webapps/dokuwiki',
    '/usr/share/dokuwiki',
    '/app/dokuwiki',
    realpath(dirname(__FILE__) . '/../../../'), # Default DokuWiki path
];

# Load doku_inc.php file
#
if (file_exists(dirname(__FILE__) . '/doku_inc.php')) {
    require_once dirname(__FILE__) . '/doku_inc.php';
}

if (!defined('DOKU_INC')) {
    foreach ($doku_inc_dirs as $dir) {
        if (!defined('DOKU_INC') && @file_exists("$dir/inc/init.php")) {
            define('DOKU_INC', "$dir/");
        }
    }
}

require_once DOKU_INC . 'inc/init.php';
require_once DOKU_PLUGIN . 'webdav/vendor/autoload.php';

use dokuwiki\plugin\webdav\core\Utils;

session_write_close();

if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
    @set_time_limit(0);
}
ignore_user_abort(true);

$base_uri = DOKU_REL . 'lib/plugins/webdav/server.php/';

try {
    $server = new dokuwiki\plugin\webdav\core\Server($base_uri);
    $server->exec();
} catch (Exception $e) {
    Utils::log('fatal', "[{class}] {message} in {file}({line})", [
        'class'   => get_class($e),
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}

Utils::log('debug', '====================');
