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

global $helper;
global $conf;

$helper = plugin_load('helper', 'webdav');

try {
    $collections         = [];
    $enabled_collections = explode(',', $helper->getConf('collections'));

    # Add pages collection
    if (in_array('pages', $enabled_collections)) {
        $collections['pages'] = new dokuwiki\plugin\webdav\types\pages\Directory();
    }

    # Add media collection
    if (in_array('media', $enabled_collections)) {
        $collections['media'] = new dokuwiki\plugin\webdav\types\media\Directory();
    }

    # Trigger PLUGIN_WEBDAV_COLLECTIONS event for add custom collections
    trigger_event('PLUGIN_WEBDAV_COLLECTIONS', $collections, null, false);

    Utils::log('debug', 'Loaded collections: {collections}', ['collections' => implode(', ', array_keys($collections))]);

    # Fix MS Office Lockroot issue
    # see: https://sabre.io/dav/clients/msoffice/

    if ($helper && $helper->getConf('fix_msoffice_lockroot')) {
        \Sabre\DAV\Xml\Property\LockDiscovery::$hideLockRoot = true;
    }

    $server = new Sabre\DAV\Server($collections);

    $server->setBaseUri(DOKU_REL . 'lib/plugins/webdav/server.php');

    # Hide SabreDAV version
    $server::$exposeVersion              = false;
    $server->enablePropfindDepthInfinity = true;

    $plugins = [
        'Mount'               => new Sabre\DAV\Mount\Plugin(),
        'Locks'               => new Sabre\DAV\Locks\Plugin(new dokuwiki\plugin\webdav\core\LocksFileBackend($conf['cachedir'] . '/webdav.lock')),
        'TemporaryFileFilter' => new Sabre\DAV\TemporaryFileFilterPlugin($conf['tmpdir'] . '/webdav'),
        'DokuWiki'            => new dokuwiki\plugin\webdav\core\DokuWikiPlugin(),
        'Exception'           => new dokuwiki\plugin\webdav\core\ExceptionPlugin(),
    ];

    $extra_tmp_file_patterns = [
        '/^~\$.*$/', // MSOffice temp files
        '/^.*.tmp$/', // Office .tmp files
        '/^.*\.wbk$/', // Word backup files
    ];

    # Add extra temporary file patterns
    foreach ($extra_tmp_file_patterns as $pattern) {
        $plugins['TemporaryFileFilter']->temporaryFilePatterns[] = $pattern;
    }

    # Add browser plugin
    if ($helper && $helper->getConf('browser_plugin')) {
        $plugins['Browser'] = new Sabre\DAV\Browser\Plugin();
    } else {
        $plugins['DummyGetResponsePlugin'] = new dokuwiki\plugin\webdav\core\DummyGetResponsePlugin();
    }

    # Some WebDAV clients do require Class 2 WebDAV support (locking), since
    # we do not provide locking we emulate it using a fake locking plugin.
    if (preg_match('/(WebDAVFS|OneNote|Microsoft-WebDAV)/', $_SERVER['HTTP_USER_AGENT'])) {
        $plugins['FakeLockerPlugin'] = new dokuwiki\plugin\webdav\core\FakeLockerPlugin();
    }

    # Enable Basic Authentication
    if ($conf['useacl']) {
        $auth_backend = new dokuwiki\plugin\webdav\core\Auth();
        $auth_backend->setRealm(hsc($conf['title']) . ' - DokuWiki WebDAV');

        $plugins['Auth'] = new Sabre\DAV\Auth\Plugin($auth_backend);
    }

    # Trigger PLUGIN_WEBDAV_PLUGINS event for add custom plugins
    trigger_event('PLUGIN_WEBDAV_PLUGINS', $plugins, null, false);

    Utils::log('debug', 'Loaded plugins: {plugins}', ['plugins' => implode(', ', array_keys($plugins))]);

    foreach ($plugins as $name => $plugin) {
        $server->addPlugin($plugin);
    }

    Utils::log('debug', 'User-Agent: {agent}', ['agent' => @$_SERVER['HTTP_USER_AGENT']]);
    Utils::log('debug', 'Remote-User: {user}', ['user' => @$_SERVER['REMOTE_USER']]);
    Utils::log('debug', 'Request-URI: {uri}', ['uri' => @$_SERVER['REQUEST_URI']]);
    Utils::log('debug', 'Request-Method: {method}', ['method' => @$_SERVER['REQUEST_METHOD']]);

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
