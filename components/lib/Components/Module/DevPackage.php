<?php
/**
 * Components_Module_DevPackage:: generates a development snapshot for the
 * specified package.
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
 * Components_Module_DevPackage:: generates a development snapshot for the
 * specified package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Module_DevPackage
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Development Packages';
    }

    public function getOptionGroupDescription()
    {
        return 'This module generates a development snapshot for the specified package';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-d',
                '--devpackage',
                array(
                    'action' => 'store_true',
                    'help'   => 'generate a development snapshot'
                )
            ),
            new Horde_Argv_Option(
                '-Z',
                '--archivedir',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the directory where any resulting source archives will be placed.'
                )
            )
        );
    }

    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['devpackage'])) {
            $this->requirePackageXml($config->getPackageDirectory());
            $this->_dependencies->getRunnerDevPackage()->run();
        }
    }
}
