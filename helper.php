<?php

/**
 * DokuWiki WebDAV Helper Class
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

class helper_plugin_webdav extends DokuWiki_Plugin
{

    /**
     * Perform access check for current user
     *
     * @return bool true if the current user has access to WebDAV
     */
    public function hasAccess()
    {
        global $conf;
        global $USERINFO;
        /** @var Input $INPUT */
        global $INPUT;

        if (!$this->getConf('remote')) {
            return false;
        }
        if (trim($this->getConf('remoteuser')) == '!!not set!!') {
            return false;
        }
        if (!$conf['useacl']) {
            return true;
        }
        if (trim($this->getConf('remoteuser')) == '') {
            return true;
        }

        return auth_isMember($this->getConf('remoteuser'), $INPUT->server->str('REMOTE_USER'), (array) $USERINFO['grps']);
    }
}
