<?php
/**
 * PEAR_Installer_Role_Horde
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
