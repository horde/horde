<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 *  We need the unit test framework
 */
require_once 'Horde/Kolab/Test/Storage.php';

/**
 *  We need some additional tools for Turba
 */
require_once 'Horde/Share.php';
require_once 'Horde/Kolab.php';

/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Turba_KolabTestBase extends Horde_Kolab_Test_Storage
{
    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runGiven($world, $action, $arguments);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runWhen($world, $action, $arguments);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        default:
            return parent::runThen($world, $action, $arguments);
        }
    }

    /**
     * Prepare the configuration.
     *
     * @return NULL
     */
    public function prepareConfiguration()
    {
    }

    /**
     * Prepare the registry.
     *
     * @return NULL
     */
    public function prepareRegistry()
    {
    }

    /**
     * Prepare the notification setup.
     *
     * @return NULL
     */
    public function prepareNotification()
    {
    }

    /**
     * Fix the read configuration.
     *
     * @return NULL
     */
    public function prepareFixedConfiguration()
    {
        $GLOBALS['conf'] = &$GLOBALS['registry']->_confCache['horde'];
        $GLOBALS['conf']['kolab']['server']['driver'] = 'test';
        $GLOBALS['conf']['documents']['type'] = 'horde';
    }

    /**
     * Prepare the Turba setup.
     *
     * @return NULL
     */
    public function prepareTurba()
    {
        $world = &$this->prepareBasicSetup();

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                       array('password' => 'none')));

        $GLOBALS['registry']->pushApp('turba', array('check_perms' => false));

        // Turba base libraries.
        require_once TURBA_BASE . '/lib/Turba.php';
        require_once TURBA_BASE . '/lib/Driver.php';
        require_once TURBA_BASE . '/lib/Object.php';

        // Turba source and attribute configuration.
        include TURBA_BASE . '/config/attributes.php';
        $cfgSources = Turba::availableSources();
        unset($cfgSources['kolab_global']);

        $this->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);
        $this->prepareNewFolder($world['storage'], 'test2', 'contact');

        $GLOBALS['session']->set('turba', 'has_share', true);
        $GLOBALS['turba_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();

        $GLOBALS['cfgSources'] = Turba::getConfigFromShares($cfgSources);
    }

    function provideServerName()
    {
        return 'localhost.localdomain';
    }

    function provideHordeBase()
    {
        require_once dirname(__FILE__) . '/../Application.php';
        return HORDE_BASE;
    }
}
