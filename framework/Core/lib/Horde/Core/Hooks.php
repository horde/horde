<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @copyright 2013-2015 Horde LLC
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * Runs Horde application hooks.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.11.0
 */
class Horde_Core_Hooks
{
    /**
     * Cached hook objects (keys are application names).
     *
     * @var array
     */
    protected $_apps = array();

    /**
     * Call a Horde hook.
     *
     * WARNING: Throwing exceptions is expensive, so use callHook() with care
     * and cache the results if you going to use the results more than once.
     *
     * @param string $hook  The hook function to call.
     * @param string $app   The hook application.
     * @param array $args   An array of any arguments to pass to the hook
     *                      function.
     *
     * @return mixed  The results of the hook.
     * @throws Horde_Exception  Thrown on error from hook code.
     * @throws Horde_Exception_HookNotSet  Thrown if hook is not active.
     */
    public function callHook($hook, $app = 'horde', array $args = array())
    {
        if (!$this->hookExists($hook, $app)) {
            throw new Horde_Exception_HookNotSet();
        }

        try {
            Horde::log(
                sprintf('Hook %s in application %s called.', $hook, $app),
                'DEBUG'
            );
            return call_user_func_array(
                array($this->_apps[$app], $hook),
                $args
            );
        } catch (Horde_Exception $e) {
            Horde::log($e, 'ERR');
            throw $e;
        }
    }

    /**
     * Returns whether a hook exists.
     *
     * Use this if you have to call a hook many times and expect the hook to
     * not exist.
     *
     * @param string $hook  The hook function.
     * @param string $app   The hook application.
     *
     * @return boolean  True if the hook exists.
     */
    public function hookExists($hook, $app = 'horde')
    {
        global $registry;

        if (!isset($this->_apps[$app])) {
            $this->_apps[$app] = false;
            $hook_class = $app . '_Hooks';
            if (!class_exists($hook_class, false)) {
                try {
                    $registry->loadConfigFile('hooks.php', null, $app);
                    $this->_apps[$app] = new $hook_class;
                } catch (Horde_Exception $e) {}
            }
        }

        return ($this->_apps[$app] && method_exists($this->_apps[$app], $hook));
    }

}
