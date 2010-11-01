<?php
/**
 * This class provides the Jonah configuration for the test script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Jonah
 */
class Jonah_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'gettext'  =>  array(
            'descrip' => 'Gettext Support',
            'error' => 'Jonah will not run without gettext support. Compile php <code>--with-gettext</code> before continuing.'
        ),
        'xml'  =>  array(
            'descrip' => 'XML Support',
            'error' => 'Without XML support, Jonah WILL NOT WORK. You must fix this before going any further.'
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
    protected $_pearList = array();

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null
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
