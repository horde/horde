<?php
/**
 * PEAR_Installer_Role_Horde postconfig script.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2011 The Horde Project (http://www.horde.org/)
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   Role
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2011 The Horde Project (http://www.horde.org/)
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   Role
 */
class Horde_Role_postinstall
{
    /**
     * PEAR config object.
     *
     * @var PEAR_Config
     */
    protected $_config;

    /**
     * Init postinstall task.
     *
     * @param PEAR_Config $config       Config object.
     * @param PEAR_PackageFile_v2 $pkg  Package object.
     * @param string $version           Last version installed.
     *
     * @returns boolean  Success.
     */
    public function init($config, $pkg, $version)
    {
        $this->_config = $config;

        return true;
    }

    /**
     * Run task after prompt.
     *
     * @param array $info   Parameter array.
     * @param string $name  Postinstall phase.
     */
    public function run($info, $phase)
    {
        if ($phase !== 'first') {
            return;
        }

        if (!$this->_config->set('horde_dir', $info['horde_dir'], 'user', 'pear.horde.org')) {
            print "Could not save horde_dir configuration value to PEAR config.\n";
            return;
        }

        $res = $this->_config->writeConfigFile();
        if ($res instanceof PEAR_Error) {
            print 'ERROR: ' . $res->getMessage() . "\n";
            exit;
        }

        print "Configuration successfully saved to PEAR config.\n";
    }

}
