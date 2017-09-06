<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Michael Slusarz <slusarz@horde.org>
 * @package  Wicked
 */

/**
 * This class provides the Wicked configuration for the test script.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Michael Slusarz <slusarz@horde.org>
 * @package  Wicked
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
        'Text_Figlet' => array(
            'error' => 'The Text_Figlet module can be used to require unauthenticated users to enter a CAPTCHA when updating pages.',
            'required' => false,
        ),
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
