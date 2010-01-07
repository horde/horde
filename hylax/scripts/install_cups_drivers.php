#!/usr/bin/php
<?php
/**
 * $Horde: incubator/hylax/scripts/install_cups_drivers.php,v 1.3 2009/06/10 19:57:57 slusarz Exp $
 */

// No need for auth.
@define('AUTH_HANDLER', true);

// Find the base file paths.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
@define('HYLAX_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HYLAX_BASE . '/lib/base.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

Horde_Cli::init();

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
