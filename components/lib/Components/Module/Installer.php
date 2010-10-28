<?php
/**
 * Components_Module_Installer:: installs a Horde element including
 * its dependencies.
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
 * Components_Module_Installer:: installs a Horde element including
 * its dependencies.
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
class Components_Module_Installer
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Installer';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module installs a Horde element including its dependencies.';
    }

    /**
     * Return the options for this module.
     *
     * @return array The group options.
     */
    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-i',
                '--install',
                array(
                    'action' => 'store',
                    'help'   => 'Install the element into the PEAR environment represented by this PEAR configuration file'
                )
            ),
            new Horde_Argv_Option(
                '-S',
                '--sourcepath',
                array(
                    'action' => 'store',
                    'help'   => 'Location of downloaded PEAR packages. Specifying this path allows you to avoid accessing the network for installing new packages.'
                )
            ),
            new Horde_Argv_Option(
                '-X',
                '--channelxmlpath',
                array(
                    'action' => 'store',
                    'help'   => 'Location of static channel XML descriptions. These files need to be named CHANNEL.channel.xml (e.g. pear.php.net.channel.xml). Specifying this path allows you to avoid accessing the network for installing new channels. If this is not specified but SOURCEPATH is given then SOURCEPATH will be checked for such channel XML files.'
                )
            ),
        );
    }

    /**
     * Determine if this module should act. Run all required actions if it has
     * been instructed to do so.
     *
     * @return NULL
     */
    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['install'])) {
            $this->requirePackageXml($config->getPackageDirectory());
            $this->_dependencies->getRunnerInstaller()->run();
        }
    }
}
