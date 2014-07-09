<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Loads generic Horde configuration files, respecting local config file
 * overrides and virtual host settings.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Registry_Loadconfig
{
    /**
     * The loaded configuration variables.
     *
     * @var array
     */
    public $config = array();

    /**
     * The PHP output from loading the files.
     *
     * @var string
     */
    public $output = '';

    /**
     * Constructor.
     *
     * @param string $app        Application.
     * @param string $conf_file  Configuration file name.
     * @param mixed $vars        List of config variables to load.
     *
     * @throws Horde_Exception
     */
    public function __construct($app, $conf_file, $vars = null)
    {
        global $conf, $registry;

        $flist = array();

        /* Load global configuration file. */
        $conf_dir = (($app == 'horde') && defined('HORDE_BASE'))
            ? HORDE_BASE . '/config/'
            : $registry->get('fileroot', $app) . '/config/';
        $flist[] = $conf_dir . $conf_file;

        $pinfo = pathinfo($conf_file);

        /* Load global configuration stanzas in '.d' directory. */
        $dir = $conf_dir . $pinfo['filename'] . '.d';
        if (is_dir($dir) && (($conf_d = glob($dir . '/*.php')) !== false)) {
            $flist = array_merge($flist, $conf_d);
        }

        /* Load local version of configuration file. */
        $flist[] = $conf_dir . $pinfo['filename'] . '.local.' . $pinfo['extension'];

        $end = count($flist) - 1;
        $load = 0;

        while (list($k, $v) = each($flist)) {
            if (file_exists($v)) {
                Horde::startBuffer();
                $success = include $v;
                $this->output .= Horde::endBuffer();

                if (!$success) {
                    throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $v));
                }

                ++$load;
            }

            if (($k === $end) && !empty($conf['vhosts'])) {
                /* Load vhost configuration file. The vhost conf.php is not
                 * determined until here because, if this is Horde, the vhost
                 * configuration variable is not available until this
                 * point. */
                $flist[] = $conf_dir . $pinfo['filename'] . '-' . $conf['server']['name'] . '.' . $pinfo['extension'];
            }
        }

        /* Return an error if no version of the config file exists. */
        if (!$load) {
            throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $conf_dir . $conf_file));
        }

        if (!is_null($vars)) {
            $this->config = compact($vars);
        }

        Horde::log(
            'Load config file (' . $conf_file . '; app: ' . $app . ')',
            'DEBUG'
        );
    }

}
