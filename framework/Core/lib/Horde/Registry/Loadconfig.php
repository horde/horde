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
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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
        $file = $conf_dir . $conf_file;
        if (file_exists($file)) {
            $flist[] = $file;
        }

        $pinfo = pathinfo($conf_file);

        /* Load global configuration stanzas in '.d' directory. */
        $dir = $conf_dir . $pinfo['filename'] . '.d';
        if (is_dir($dir)) {
            $flist = array_merge($flist, glob($dir . '/*.php'));
        }

        /* Load local version of configuration file. */
        $file = $conf_dir . $pinfo['filename'] . '.local.' . $pinfo['extension'];
        if (file_exists($file)) {
            $flist[] = $file;
        }

        foreach ($flist as $val) {
            Horde::startBuffer();
            $success = include $val;
            $this->output .= Horde::endBuffer();

            if (!$success) {
                throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $val));
            }
        }

        /* Load vhost configuration file. The vhost conf.php is not determined
         * until here because, if this is Horde, the vhost configuration
         * variable is not available until this point. */
        if (!empty($conf['vhosts'])) {
            $file = $conf_dir . $pinfo['filename'] . '-' . $conf['server']['name'] . '.' . $pinfo['extension'];

            if (file_exists($file)) {
                Horde::startBuffer();
                $success = include $file;
                $this->output .= Horde::endBuffer();

                if (!$success) {
                    throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $file));
                }

                /* Add to filelist to satisfy check below that at least one
                 * file was loaded. */
                $flist[] = $file;
            }
        }

        /* Return an error if no version of the config file exists. */
        if (empty($flist)) {
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
