#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('cli' => true));

/* The CUPS backend file. */
$hylafax_backend = file_get_contents(HYLAX_BASE . '/scripts/cups/hylafax');
$path = realpath(HYLAX_BASE . '/scripts');
$hylafax_backend = preg_replace('/__SCRIPT_DIR__/', $path, $hylafax_backend);
$hylafax_backend = preg_replace('/__WWW_USER__/', $conf['cups']['www_user'], $hylafax_backend);
$fp = fopen($conf['cups']['backend_dir'] . '/hylafax', 'w');
fwrite($fp, $hylafax_backend);
fclose($fp);
chmod($conf['cups']['backend_dir'] . '/hylafax', 0755);

/* The PPD model file. */
$hylafax_ppd = file_get_contents(HYLAX_BASE . '/scripts/cups/hylafax.ppd');
$fp = fopen($conf['cups']['ppd_dir'] . '/hylafax.ppd', 'w');
fwrite($fp, $hylafax_ppd);
fclose($fp);
chmod($conf['cups']['ppd_dir'] . '/hylafax.ppd', 0644);

/* The faxrcvd script. */
if (!file_exists($conf['hylafax']['faxrcvd'] . '/faxrcvd.orig')) {
    $orig_faxrcvd = file_get_contents($conf['hylafax']['faxrcvd'] . '/faxrcvd');
    $fp = fopen($conf['hylafax']['faxrcvd'] . '/faxrcvd.orig', 'w');
    fwrite($fp, $orig_faxrcvd);
    fclose($fp);
}
$faxrcvd = file_get_contents(HYLAX_BASE . '/scripts/hylafax/faxrcvd');
$faxrcvd = preg_replace('/__SCRIPT_DIR__/', $path, $faxrcvd);
$faxrcvd = preg_replace('/__WWW_USER__/', $conf['cups']['www_user'], $faxrcvd);
$fp = fopen($conf['hylafax']['faxrcvd'] . '/faxrcvd', 'w');
fwrite($fp, $faxrcvd);
fclose($fp);
chmod($conf['hylafax']['faxrcvd'] . '/faxrcvd', 0755);
