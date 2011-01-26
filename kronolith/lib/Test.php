<?php
/**
 * This class provides the Kronolith configuration for the test script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array();

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
        'Date' => array(
            'path' => 'Date/Calc.php',
            'error' => 'Kronolith requires the Date_Calc class to calculate dates.',
            'required' => true,
        ),
        'Date_Holidays' => array(
            'error' => 'Date_Holidays can be used to calculate and display national and/or religious holidays.',
            'required' => false,
        ),
        'XML_Serializer' => array(
            'path' => 'XML/Unserializer.php',
            'error' => 'The XML_Serializer might be needed by the Date_Holidays package for the translation of holidays',
            'required' => false,
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null,
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
