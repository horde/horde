<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

$no_compress = true;

$title = _("Download File");

@define('BABEL_BASE', dirname(__FILE__));
require_once BABEL_BASE . '/lib/base.php';

$files = array();
$dirs = Translate::search_applications();
foreach($dirs as $d => $dir) {
    $dir = realpath($dir);
    
    $app = str_replace(realpath(HORDE_BASE), '', $dir);
    $app = str_replace('/', '', $app);
    if (empty($app)) {
	$app = 'horde';
    }

    $po  = $dir . '/po/' . $lang . '.po';
    if (@file_exists($po)) {
	$files[$app] = $po;
    }
}

$filename = "po-" . $lang . ".zip";
@system("rm -rf /tmp/$filename");
@system("rm -rf /tmp/translate");
@mkdir("/tmp/translate");
foreach($files as $app => $file) {
    $cmd = "cp $file /tmp/translate/$app-" . basename($file);
    @system($cmd);
}
$cmd = "zip -j /tmp/$filename /tmp/translate/*";
@exec($cmd);

$data = file_get_contents("/tmp/$filename");

$browser->downloadHeaders($filename);
echo $data;
