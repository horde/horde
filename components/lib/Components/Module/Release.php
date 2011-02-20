<?php
/**
 * Components_Module_Release:: generates a release.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Module_Release:: generates a release.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Module_Release
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Package release';
    }

    public function getOptionGroupDescription()
    {
        return 'This module releases a new version for the specified package';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-r',
                '--release',
                array(
                    'action' => 'store_true',
                    'help'   => 'Release the next version of the package.'
                )
            ),
            new Horde_Argv_Option(
                '-M',
                '--releaseserver',
                array(
                    'action' => 'store',
                    'help'   => 'The remote server SSH connection string. The release package will be copied here via "scp".'
                )
            ),
            new Horde_Argv_Option(
                '-U',
                '--releasedir',
                array(
                    'action' => 'store',
                    'help'   => 'PEAR server target directory on the remote machine.'
                )
            ),
        );
    }

    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['release'])) {
            $this->requirePackageXml($config->getPackageDirectory());
            $this->_dependencies->getRunnerRelease()->run();
        }
    }
}
