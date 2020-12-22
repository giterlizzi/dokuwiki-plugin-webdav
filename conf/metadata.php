<?php

/**
 * Options for the WebDAV Plugin
 *
 * @author Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 */

$meta['remote']                = ['onoff', '_caution' => 'security'];
$meta['remoteuser']            = ['string'];
$meta['fix_msoffice_lockroot'] = ['onoff'];
$meta['show_button']           = ['onoff'];
$meta['browser_plugin']        = ['onoff'];
$meta['collections']           = ['multicheckbox', '_choices' => ['pages', 'media', 'odt', 'tags']];
