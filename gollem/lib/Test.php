<?php
/**
 * This class provides the Gollem configuration for the test script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Gollem
 */
class Gollem_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'ftp' => array(
            'descrip' => 'FTP Support',
            'error' => 'You need FTP support compiled into PHP if you plan to use the FTP VFS driver (see config/backends.php).'
        ),
        'ssh2' => array(
            'descrip' => 'SSH2 Support',
            'error' => 'You need the SSH2 PECL module if you plan to use the SSH2 VFS driver (see config/backends.php).'
        )
    );

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array();

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array(
        'HTTP_WebDAV_Server' => array(
            'error' => 'You do not have the HTTP_WebDAV_Server package installed on your system. This module is required to use browse the VFS using WebDAV.  See the INSTALL file for instructions on how to install the package.'
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/backends.php' => null,
        'config/conf.php' => null,
        'config/mime_drivers.php' => null,
        'config/prefs.php' => null
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array();

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }

}
