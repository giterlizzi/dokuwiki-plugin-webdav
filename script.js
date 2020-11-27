/*!
 * DokuWiki WebDAV Plugin
 *
 * Home      http://dokuwiki.org/plugin:webdav
 * Author    Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * License   GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * Copyright (C) 2020, Giuseppe Di Terlizzi
 */

jQuery('.plugin_webdav').on('click', function () {

    var $self = jQuery(this);

    var webdav_url = $self.attr('data-webdav-url');
    var clients = {
        'nautilus': webdav_url.replace(/^http/, 'dav'),
        'dolphin': webdav_url.replace(/^http/, 'webdav'),
        'cmd_exe': 'net use Z: '+ webdav_url +' /user:youruser yourpassword',
    };

    var dialog_html = '<div class="plugin_webdav_dialog">'
        + '<h4>WebDAV URL</h4>'
        + '<p>' + webdav_url + '</p>'
        + '<hr/>'
        + '<h4>URL for WebDAV clients</h4>'
        + '<table class="inline table"><tbody>'
        + '<tr><th>Nautilus</th><td>' + clients.nautilus + '</td></tr>'
        + '<tr><th>KDE Dolphin & Konqueror</th><td>' + clients.dolphin + '</td></tr>'
        + '<tr><th>Linux (davfs2, Cadaver)</th><td>' + webdav_url + '</td></tr>'
        + '<tr><th>MacOS Finder</th><td>' + webdav_url + '</td></tr>'
        + '<tr><th>Windows Explorer</th><td>' + webdav_url + '</td></tr>'
        + '<tr><th>Windows (cmd.exe)</th><td><kbd>' + clients.cmd_exe + '</kbd></td></tr>'
        + '</tbody></table>'
        + '</div>';

    jQuery(dialog_html).dialog({
        modal: true,
        title: 'WebDAV',
        width: '60%'
    });

});
