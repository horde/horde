<?php
/**
 * PEAR_Installer_Role_Horde
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Role
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Role
 */
class PEAR_Installer_Role_Horde extends PEAR_Installer_Role_Common
{
    function setup(&$installer, $pkg, $atts, $file)
    {
        /* Check for proper setup. */
        $pear_config = PEAR_Config::singleton();
        if (!$pear_config->get('horde_dir')) {
            return PEAR::raiseError('Missing "horde_dir" configuration in PEAR.');
        }
    }
}
