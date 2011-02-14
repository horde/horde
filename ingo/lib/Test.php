<?php
/**
 * This class provides the Ingo configuration for the test script.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'ftp' => array(
            'descrip' => 'FTP Support',
            'error' => 'If you will be using the VFS FTP driver for procmail scripts, PHP must have FTP support. Compile PHP <code>--with-ftp</code> before continuing.'
        ),
        'ssh2' => array(
            'descrip' => 'SSH2 Support',
            'error' => 'You need the SSH2 PECL module if you plan to use the SSH2 VFS driver to store procmail scripts on the mail server.'
        ),
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
        'Net_Socket' => array(
            'error' => 'If you will be using Sieve scripts, make sure you are using a version of PEAR which includes the Net_Socket class, or that you have installed the Net_Socket package seperately.'
        ),
        'Net_Sieve' => array(
            'error' => 'If you will be using Sieve scripts, make sure you are using a version of PEAR which includes the Net_Sieve class, or that you have installed the Net_Sieve package seperately.'
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null,
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array(
        'imp' => array(
            'error' => 'IMP can be used to interface ingo with a mailserver.',
            'version' => '5.0'
        )
    );

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }

}
