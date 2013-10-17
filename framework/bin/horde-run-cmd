#!/usr/bin/env php
<?php
/**
 * Runs a shell command (contained in $argv[1]) in the base of every
 * application and library contained in the current git checkout.
 */

function horde_get_base_dirs()
{
    $basedirs = array(
        dirname(__FILE__) . '/../',
        dirname(__FILE__) . '/../../'
    );

    $out = array();

    foreach ($basedirs as $base) {
        foreach (new DirectoryIterator($base) as $val) {
            if ($val->isDir() &&
                file_exists($val->getPathname() . '/package.xml')) {
                $out[] = realpath($val->getPathname());
            }
        }
    }

    return $out;
}

foreach (horde_get_base_dirs() as $val) {
    chdir($val);
    system($argv[1]);
}
