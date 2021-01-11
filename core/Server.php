<?php

/**
 * DokuWiki WebDAV Plugin: DAV Server
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

namespace dokuwiki\plugin\webdav\core;

use dokuwiki\plugin\webdav\core\DAV\Collection;
use Sabre\DAV;

class Server
{
    /**
     * DAV Server
     *
     * @var DAV\Server
     */
    public $server;

    /**
     * Create DAV Server
     *
     * @param string $base_uri
     * @param array $tree collections
     * @return DAV\Server
     */
    public function __construct($base_uri)
    {
        global $conf;
        global $helper;

        $helper = plugin_load('helper', 'webdav');

        $wiki_collections    = [];
        $enabled_collections = explode(',', $helper->getConf('collections'));

        # Add pages collection
        if (in_array('pages', $enabled_collections)) {
            $wiki_collections[] = new Collection\Pages\Directory();
        }

        # Add media collection
        if (in_array('media', $enabled_collections)) {
            $wiki_collections[] = new Collection\Media\Directory();
        }

        # Trigger PLUGIN_WEBDAV_COLLECTIONS event for add custom collections
        trigger_event('PLUGIN_WEBDAV_WIKI_COLLECTIONS', $wiki_collections, null, false);

        $this->server = new DAV\Server(new DAV\SimpleCollection('root', [
            new DAV\SimpleCollection('wiki', $wiki_collections),
        ]));

        if ($base_uri) {
            $this->server->setBaseUri($base_uri);
        }

        # Hide SabreDAV version
        $this->server::$exposeVersion = false;

        # Add Exception plugin
        $this->server->addPlugin(new Plugin\Exception);

        # Add browser or dummy response plugin
        if ($helper->getConf('browser_plugin')) {
            $this->server->addPlugin(new DAV\Browser\Plugin);
        } else {
            $this->server->addPlugin(new Plugin\DummyGetResponse);
        }

        # Enable Basic Authentication using DokuWiki Authentication
        if ($conf['useacl']) {
            $auth_backend = new Backend\Auth();
            $auth_backend->setRealm(hsc($conf['title']) . ' - DokuWiki WebDAV');

            $this->server->addPlugin(new DAV\Auth\Plugin($auth_backend));
        }

        # WebDAV plugins
        $this->server->addPlugin(new DAV\Mount\Plugin);
        $this->server->addPlugin(new DAV\Locks\Plugin(new Backend\LocksFile($conf['cachedir'] . '/webdav.lock')));
        $this->server->addPlugin(new Plugin\DokuWiki);

        $extra_tmp_file_patterns = [
            '/^~\$.*$/', // MSOffice temp files
            '/^.*.tmp$/', // Office .tmp files
            '/^.*\.wbk$/', // Word backup files
        ];

        $tmp_file_filter_plugin = new DAV\TemporaryFileFilterPlugin($conf['tmpdir'] . '/webdav');

        # Add extra temporary file patterns
        foreach ($extra_tmp_file_patterns as $pattern) {
            $tmp_file_filter_plugin->temporaryFilePatterns[] = $pattern;
        }

        $this->server->addPlugin($tmp_file_filter_plugin);

        # Some WebDAV clients do require Class 2 WebDAV support (locking), since
        # we do not provide locking we emulate it using a fake locking plugin.
        if (preg_match('/(WebDAVFS|OneNote|Microsoft-WebDAV)/', $_SERVER['HTTP_USER_AGENT'])) {
            $this->server->addPlugin(new Plugin\FakeLocker);
        }

        # Custom plugins
        $plugins = [];

        # Trigger PLUGIN_WEBDAV_PLUGINS event for add custom plugins
        trigger_event('PLUGIN_WEBDAV_PLUGINS', $plugins, null, false);

        foreach ($plugins as $plugin) {
            $this->server->addPlugin($plugin);
        }

        return $this->server;
    }

    /**
     * Run DAV server
     *
     * @return void
     */
    public function exec()
    {
        Utils::log('debug', 'User-Agent: {agent}', ['agent' => @$_SERVER['HTTP_USER_AGENT']]);
        Utils::log('debug', 'Remote-User: {user}', ['user' => @$_SERVER['REMOTE_USER']]);
        Utils::log('debug', 'Request-URI: {uri}', ['uri' => @$_SERVER['REQUEST_URI']]);
        Utils::log('debug', 'Request-Method: {method}', ['method' => @$_SERVER['REQUEST_METHOD']]);

        $this->server->exec();
    }
}
