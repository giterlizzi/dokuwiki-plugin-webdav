<?php

/**
 * Dokuwiki WebDAV Plugin: Admin Interface
 *
 * @link     https://dokuwiki.org/plugin:webdav
 * @author   Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

require_once DOKU_PLUGIN . 'webdav/vendor/autoload.php';

use Sabre\DAV\Locks\LockInfo;

class admin_plugin_webdav extends DokuWiki_Admin_Plugin
{
    /** @inheritDoc */
    public function getMenuSort()
    {
        return 1;
    }

    /** @inheritDoc */
    public function forAdminOnly()
    {
        return false;
    }

    /** @inheritDoc */
    public function getMenuIcon()
    {
        return dirname(__FILE__) . '/folder-network-outline.svg';
    }

    /** @inheritDoc */
    public function getMenuText($language)
    {
        return 'WebDAV';
    }

    /** @inheritDoc */
    public function handle()
    {
        global $INPUT;

        if (!$_REQUEST['cmd']) {
            return;
        }

        if (!checkSecurityToken()) {
            return;
        }

        $cmd = $INPUT->extract('cmd')->str('cmd');

        $dispatch = [
            'unlock' => 'unlockFile',
        ];

        if ($cmd) {
            if (!isset($dispatch[$cmd])) {
                msg('Unknown command', -1);
                return;
            }
            call_user_func([$this, $dispatch[$cmd]]);
        }
    }

    /**
     * Unlock file
     *
     * @return bool
     */
    public function unlockFile()
    {
        global $INPUT;
        global $conf;

        $lock_id     = $INPUT->str('lock');
        $locks_file  = $conf['cachedir'] . '/webdav.lock';
        $locked_file = '';

        if (!$lock_id) {
            msg('No lock provided', -1);
            return;
        }

        if ($locks = $this->getLocks()) {
            foreach ($locks as $id => $lock) {
                if ($lock->token == $lock_id) {
                    $locked_file = $lock->uri;
                    unset($locks[$id]);
                }
            }

            if ($locked_file) {
                if (!io_saveFile($locks_file, serialize($locks))) {
                    msg('Unlock failed', -1);
                    return;
                }

                msg("File $locked_file successfully unlocked", 1);
            }
        }
    }

    /** @inheritDoc */
    public function html()
    {
        echo '<div id="plugin_advanced_export">';
        echo $this->locale_xhtml('intro');

        echo '<form action="" method="post" class="form-inline">';

        $this->displayLocks();

        formSecurityToken();

        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="webdav" />';
        echo '<input type="hidden" name="cmd" value="unlock" />';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Display active locks
     */
    private function displayLocks()
    {
        echo '<h3>Locks</h3>';
        echo '<table class="inline" style="width:100%">';
        echo '<thead>
            <tr>
                <th>Owner</th>
                <th>Timeout</th>
                <th>Created</th>
                <th>Type</th>
                <th>URI</th>
                <th>User Agent</th>
                <th>&nbsp;</th>
            </tr>
        </thead>';
        echo '<tbody>';
        foreach ($this->getLocks() as $lock) {
            $pathinfo = pathinfo($lock->uri);
            echo '<tr>';
            echo '<td><a href="#" class="interwiki iw_user"></a>' . (($lock->owner == $lock->user) ? $lock->owner : $lock->user . ' (' . $lock->owner . ')') . '</td>';
            echo "<td>{$lock->timeout} seconds</td>";
            echo '<td>' . datetime_h($lock->created) . '<br><small>(' . dformat($lock->created) . ')</small></td>';
            echo "<td>{$this->getLockType($lock->scope)}</td>";
            echo '<td><a class="mediafile mf_' . $pathinfo['extension'] . '" href="' . getBaseURL(true) . 'lib/plugins/webdav/server.php/' . hsc($lock->uri) . '">' . $lock->uri . '</a></td>';
            echo "<td>{$lock->ua}</td>";
            echo '<td><button type="submit" class="btn btn-default btn-xs btn_unlock_file" name="lock" value="' . $lock->token . '">Unlock</button></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get active locks
     *
     * @return array
     */
    private function getLocks()
    {
        global $conf;

        $locks      = [];
        $locks_file = $conf['cachedir'] . '/webdav.lock';

        if (file_exists($locks_file)) {
            $locks = unserialize(io_readFile($locks_file));
        }
        return $locks;
    }

    /**
     * Get lock type
     *
     * @param string $scope
     * @return string
     */
    private function getLockType($scope)
    {
        switch ($scope) {
            case LockInfo::EXCLUSIVE:
                return 'Exclusive';
            case LockInfo::SHARED:
                return 'Shared';
            case LockInfo::TIMEOUT_INFINITE:
                return 'Timeout Infinite';
            default:
                return 'N/A';
        }
    }
}
