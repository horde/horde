<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Parses registry configuration files into applications and interfaces
 * arrays.
 *
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Registry_Registryconfig
{
    /**
     * Hash storing information on each registry-aware application.
     *
     * @var array
     */
    public $applications = array();

    /**
     * Interfaces list.
     *
     * @var array
     */
    public $interfaces = array();

    /**
     * Constructor.
     *
     * @param Horde_Registry $reg_ob  Registry object.
     *
     * @throws Horde_Exception
     */
    public function __construct($reg_ob)
    {
        /* Read the registry configuration files. */
        if (!file_exists(HORDE_BASE . '/config/registry.php')) {
            throw new Horde_Exception('Missing registry.php configuration file');
        }

        /* Set textdomain to Horde, so that we really only load translations
         * from Horde. */
        $app = $reg_ob->getApp();
        if ($app != 'horde') {
            textdomain('horde');
        }

        require HORDE_BASE . '/config/registry.php';
        if ($files = glob(HORDE_BASE . '/config/registry.d/*.php')) {
            foreach ($files as $r) {
                include $r;
            }
        }
        if (file_exists(HORDE_BASE . '/config/registry.local.php')) {
            include HORDE_BASE . '/config/registry.local.php';
        }
        if ($reg_ob->vhost) {
            include $reg_ob->vhost;
        }

        /* Reset textdomain. */
        if ($app != 'horde') {
            textdomain($app);
        }

        if (!isset($this->applications['horde']['fileroot'])) {
            $this->applications['horde']['fileroot'] = isset($app_fileroot)
                ? $app_fileroot
                : HORDE_BASE;
        }
        if (!isset($app_fileroot)) {
            $app_fileroot = $this->applications['horde']['fileroot'];
        }

        /* Make sure the fileroot of Horde has a trailing slash to not trigger
         * open_basedir restrictions that have that trailing slash too. */
        $app_fileroot = rtrim($app_fileroot, '/') . '/';

        if (!isset($this->applications['horde']['webroot'])) {
            $this->applications['horde']['webroot'] = isset($app_webroot)
                ? $app_webroot
                : $this->_detectWebroot();
        }
        if (!isset($app_webroot)) {
            $app_webroot = $this->applications['horde']['webroot'];
        }

        if (!isset($this->applications['horde']['staticfs'])) {
            $this->applications['horde']['staticfs'] =
                $this->applications['horde']['fileroot'] . '/static';
        }
        if (!isset($this->applications['horde']['staticuri'])) {
            $this->applications['horde']['staticuri'] =
                $this->applications['horde']['webroot'] . '/static';
        }

        /* Scan for all APIs provided by each app, and set other common
         * defaults like templates and graphics. */
        foreach ($this->applications as $appName => &$app) {
            if (!isset($app['status'])) {
                $app['status'] = 'active';
            } elseif ($app['status'] == 'heading' ||
                      $app['status'] == 'topbar'  ||
                      $app['status'] == 'link') {
                continue;
            }

            $app['fileroot'] = isset($app['fileroot'])
                ? rtrim($app['fileroot'], ' /')
                : $app_fileroot . $appName;

            if (!isset($app['name'])) {
                $app['name'] = '';
            }

            if (!file_exists($app['fileroot']) ||
                (!$reg_ob->isTest() &&
                 file_exists($app['fileroot'] . '/config/conf.xml') &&
                 !file_exists($app['fileroot'] . '/config/conf.php'))) {
                $app['status'] = 'inactive';
                Horde::log('Setting ' . $appName . ' inactive because the fileroot does not exist or the application is not configured yet.', 'DEBUG');
            }

            $app['webroot'] = isset($app['webroot'])
                ? rtrim($app['webroot'], ' /')
                : $app_webroot . '/' . $appName;

            if (($app['status'] != 'inactive') &&
                isset($app['provides']) &&
                (($app['status'] != 'admin') || $reg_ob->isAdmin())) {
                if (is_array($app['provides'])) {
                    foreach ($app['provides'] as $interface) {
                        $this->interfaces[$interface] = $appName;
                    }
                } else {
                    $this->interfaces[$app['provides']] = $appName;
                }
            }

            if (!isset($app['templates']) && isset($app['fileroot'])) {
                $app['templates'] = $app['fileroot'] . '/templates';
            }
            if (!isset($app['jsuri']) && isset($app['webroot'])) {
                $app['jsuri'] = $app['webroot'] . '/js';
            }
            if (!isset($app['jsfs']) && isset($app['fileroot'])) {
                $app['jsfs'] = $app['fileroot'] . '/js';
            }
            if (!isset($app['themesuri']) && isset($app['webroot'])) {
                $app['themesuri'] = $app['webroot'] . '/themes';
            }
            if (!isset($app['themesfs']) && isset($app['fileroot'])) {
                $app['themesfs'] = $app['fileroot'] . '/themes';
            }
        }
    }

    /**
     * Attempt to auto-detect the Horde webroot.
     *
     * @return string  The webroot.
     */
    protected function _detectWebroot()
    {
        // Note for Windows: the below assumes the PHP_SELF variable uses
        // forward slashes.
        if (isset($_SERVER['SCRIPT_URL']) || isset($_SERVER['SCRIPT_NAME'])) {
            $path = empty($_SERVER['SCRIPT_URL'])
                ? $_SERVER['SCRIPT_NAME']
                : $_SERVER['SCRIPT_URL'];
            $hordedir = basename(str_replace(DIRECTORY_SEPARATOR, '/', realpath(HORDE_BASE)));
            return (preg_match(';/' . $hordedir . ';', $path))
                ? preg_replace(';/' . $hordedir . '.*;', '/' . $hordedir, $path)
                : '';
        }

        if (!isset($_SERVER['PHP_SELF'])) {
            return '/horde';
        }

        $webroot = preg_split(';/;', $_SERVER['PHP_SELF'], 2, PREG_SPLIT_NO_EMPTY);
        $webroot = strstr(realpath(HORDE_BASE), DIRECTORY_SEPARATOR . array_shift($webroot));
        if ($webroot !== false) {
            return preg_replace(array('/\\\\/', ';/config$;'), array('/', ''), $webroot);
        }

        return ($webroot === false)
            ? ''
            : '/horde';
    }

}
