<?php

/**
 * DokuWiki WebDAV Plugin: Auth Backend
 *
 * @author  Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @link    https://dokuwiki.org/plugin:webdav
 */

namespace dokuwiki\plugin\webdav\core\Backend;

use dokuwiki\plugin\webdav\core\Utils;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class Auth extends AbstractBasic
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

        $helper = plugin_load('helper', 'webdav');

        $check = $auth->checkPass($username, $password);

        if (!$helper->hasAccess()) {
            Utils::log('fatal', 'Unauthorized. Check webdav.remoteuser option');
            return false;
        }

        return $check;
    }
}
