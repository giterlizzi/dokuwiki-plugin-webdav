<?php

/**
 * DokuWiki WebDAV Auth Backend
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\core;

use Sabre\DAV;
use dokuwiki\plugin\webdav\core;

class Auth extends DAV\Auth\Backend\AbstractBasic
{
    /**
     * Validate user credential
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function validateUserPass($username, $password)
    {
        global $auth;
        global $conf;
        global $helper;

        $check = $auth->checkPass($username, $password);

        core\Utils::log('debug', '[Auth] {check} password for {username} user', [
            'username' => $username,
            'check'    => ($check ? 'Valid' : 'Invalid'),
        ]);

        if (!$helper->hasAccess()) {
            core\Utils::log('debug', '[Auth] Access denied. See WebDAV "remoteuser" config');
            return false;
        }

        return $check;
    }
}
