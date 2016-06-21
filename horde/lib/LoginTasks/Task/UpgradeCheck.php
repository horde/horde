<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

/**
 * Login task to check for Horde upgrades, and then report upgrades to an admin
 * via the notification system.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_LoginTasks_Task_UpgradeCheck extends Horde_LoginTasks_Task
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::WEEKLY;

    /**
     * Display type.
     *
     * @var integer
     */
    public $display = Horde_LoginTasks::DISPLAY_NONE;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['registry']->isAdmin();
    }

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        global $notification, $registry;

        $hconfig = new Horde_Config();
        try {
            $versions = $hconfig->checkVersions();
            foreach ($versions as &$app) {
                $app['version'] = preg_replace(
                    '/H\d \((.*)\)/', '$1', $app['version']
                );
            }
        } catch (Horde_Exception $e) {
            return;
        }

        $pearConfig = PEAR_Config::singleton();
        $packageFile = new PEAR_PackageFile($pearConfig);
        $packages = array();
        foreach ($pearConfig->getRegistry()->packageInfo(null, null, 'pear.horde.org') as $package) {
            $packages[$package['name']] = $package['version']['release'];
        }

        $configLink = Horde::link(
            Horde::url('admin/config/index.php', false, array('app' => 'horde'))
        );
        if (class_exists('Horde_Bundle') &&
            isset($versions[Horde_Bundle::NAME]) &&
            version_compare($versions[Horde_Bundle::NAME]['version'], Horde_Bundle::VERSION, '>')) {
            $notification->push(
                $configLink . sprintf(
                    _("A newer version of %s exists."), Horde_Bundle::FULLNAME
                ) . '</a>',
                'horde.warning',
                array('content.raw', 'sticky')
            );
            return;
        }

        foreach ($registry->listAllApps() as $app) {
            if (($version = $registry->getVersion($app, true)) &&
                isset($versions[$app]) &&
                version_compare($versions[$app]['version'], $version, '>')) {
                $notification->push(
                    $configLink . _("A newer version of an application exists.") . '</a>',
                    'horde.warning',
                    array('content.raw', 'sticky')
                );
                return;
            }
        }

        foreach ($packages as $app => $version) {
            if (isset($versions[$app]) &&
                version_compare($versions[$app]['version'], $version, '>')) {
                $notification->push(
                    $configLink . _("A newer version of a library exists.") . '</a>',
                    'horde.warning',
                    array('content.raw', 'sticky')
                );
                return;
            }
        }
    }
}
