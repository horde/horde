<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_LoginTasks_SystemTask_Upgrade extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::EVERY;

    /**
     * The current application.
     *
     * @var string
     */
    protected $_app = 'horde';

    /**
     * Do these upgrade tasks require authentication?
     *
     * @var boolean
     */
    protected $_auth = false;

    /**
     * The list of versions to upgrade.
     *
     * @var array
     */
    protected $_toupgrade = array();

    /**
     * The list of versions which upgrades will occur.
     *
     * @var array
     */
    protected $_versions = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        usort($this->_versions, 'version_compare');

        if ($vers = $this->_pref('get')) {
            foreach ($this->_versions as $val) {
                /* Our versioning system is not compatible with PHP's
                 * version_compare, since x.0.foo is ALWAYS greater than
                 * x.0foo. */
                $compare = (substr_count($val, '.') != substr_count($vers, '.'))
                    ? preg_replace("/(\.0)((?:alpha|beta|RC)\d+)/i", "$2", $vers)
                    : $vers;
                if (version_compare($compare, $val) === -1) {
                    $this->_toupgrade[] = $val;
                }
            }
        } else {
            $this->_toupgrade = $this->_versions;
        }

        $this->active = !empty($this->_toupgrade);
    }

    /**
     * Perform upgrade tasks.
     */
    public function execute()
    {
        foreach ($this->_toupgrade as $val) {
            $this->_upgrade($val);
        }

        $this->_pref('set');
    }

    /**
     * Force re-run of all upgrade tasks.
     */
    public function forceUpgrade()
    {
        $this->active = true;
        $this->_toupgrade = $this->_versions;
        $this->execute();
    }

    /**
     * Perform upgrade tasks for a given version.
     *
     * For those running a git checkout, the system task for a given version
     * will run continuously until that version is released. Code should
     * be added to not convert already converted values.
     *
     * @param string $version  A version string.
     */
    abstract protected function _upgrade($version);

    /**
     */
    public function skip()
    {
        /* Skip task until we are authenticated. */
        return ($this->_auth &&
                !$GLOBALS['registry']->isAuthenticated(array('app' => $this->_app)));
    }

    /**
     * Manage the upgrade preferences.
     *
     * @param string $action  Either 'get' or 'set'.
     *
     * @return string  The current version.
     */
    protected function _pref($action)
    {
        global $prefs, $registry;

        $key = $this->_app;
        if ($this->_auth) {
            $key .= '_auth';
        }

        $upgrade = @unserialize($prefs->getValue('upgrade_tasks'));

        switch ($action) {
        case 'get':
            $val = isset($upgrade[$key])
                ? $upgrade[$key]
                : null;
            break;

        case 'set':
            $val = $registry->getVersion($this->_app, true);
            $upgrade[$key] = $val;
            $prefs->setValue('upgrade_tasks', serialize($upgrade));
            break;
        }

        return $val;
    }

}
