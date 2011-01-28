<?php
/**
 * This class provides the Wicked configuration for the test script.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Wicked
 */
class Wicked_Test extends Horde_Test
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
        'Text_Wiki' => array(
            'error' => 'The Text_Wiki module is required to parse and render the wiki markup in Wicked.',
            'required' => true,
            'function' => '_checkPearTextWikiVersion'
        ),
        'Text_Wiki_BBCode' => array(
            'error' => 'The Text_Wiki_BBCode module is required if you plan on using BBCode formatting.',
            'required' => false,
        ),
        'Text_Wiki_Cowiki' => array(
            'error' => 'The Text_Wiki_Cowiki module is required if you plan on using Cowiki formatting.',
            'required' => false,
        ),
        'Text_Wiki_Creole' => array(
            'error' => 'The Text_Wiki_Creole module is required if you plan on using Creole formatting.',
            'required' => false,
        ),
        'Text_Wiki_Mediawiki' => array(
            'error' => 'The Text_Wiki_Mediawiki module is required if you plan on using Mediawiki formatting.',
            'required' => false,
        ),
        'Text_Wiki_Tiki' => array(
            'error' => 'The Text_Wiki_Tiki module is required if you plan on using Tiki formatting.',
            'required' => false,
        ),
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
     * Additional check for PEAR Text_Wiki module for its version.
     *
     * @return string  Returns error string on error.
     */
    protected function _checkPearTextWikiVersion()
    {
        if (!is_callable(array('Text_Wiki', 'setRenderConf'))) {
            return 'Your version of Text_Wiki is not recent enough.';
        }
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
