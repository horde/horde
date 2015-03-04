<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * This class provides the Ingo configuration for the test script.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
    protected $_pearList = array();

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array(
        'imp' => array(
            'error' => 'IMP can be used to interface ingo with a mailserver.',
            'version' => '6.0'
        )
    );

    /**
     */
    public function __construct()
    {
        parent::__construct();

        $this->_fileList += array(
            'config/backends.php' => null,
            'config/fields.php' => null,
            'config/prefs.php' => null
        );
    }

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }

}
