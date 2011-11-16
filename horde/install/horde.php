<?php
/**
 * Horde post-install script.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011 Horde LLC (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Horde
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011 Horde LLC (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Horde
 */
class install_horde_postinstall
{
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
        switch ($phase) {
        case 'first':
            if (strtolower($info['clear_cache']) == 'y') {
                passthru('../bin/horde-clear-cache -f');
            }
            break;
        }
    }

}
