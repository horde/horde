<?php
/**
 * This file contains all the configuration information for the various
 * sources to display. The following fields are defined:
 *
 * 'name'          : Short name for the repository
 *
 * 'driver'        : The driver to use when accessing this source. Currently
 *                   only 'plain' is implemented, eventually 'cvs' will be
 *                   supported as well.
 *
 * 'root'          : Location on the filesystem of the source
 *
 * 'restrictions'  : Array of perl-style regular expressions for those files
 *                   whose contents should be protected and not displayed.
 *
 * $Id$
 */

$sources['horde'] = array(
    'name' => 'Horde',
    'driver' => 'plain',
    'root' => dirname(__FILE__) . '/../../',
    'restrictions' => array('(.*)config/(\w*).php$')
);

$sources['pear'] = array(
    'name' => 'PEAR',
    'driver' => 'plain',
    'root' => '/usr/local/lib/php/'
);
