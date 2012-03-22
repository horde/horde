<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */


/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Turba_KolabTestBase extends Turba_TestCase
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

        $GLOBALS['cfgSources'] = Turba::getConfigFromShares($cfgSources);
    }

    function provideServerName()
    {
        return 'localhost.localdomain';
    }

    function provideHordeBase()
    {
        require_once __DIR__ . '/../Application.php';
        return HORDE_BASE;
    }
}
