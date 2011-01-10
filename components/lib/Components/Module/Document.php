<?php
/**
 * Components_Module_Document:: generates HTML documentation for a
 * component.
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
 * Components_Module_Document:: generates HTML documentation for a
 * component.
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
class Components_Module_Document
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Document';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module generates HTML documentation for the component.';
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
                '-O',
                '--document',
                array(
                    'action' => 'store',
                    'help'   => 'Generate the documentation for the component in the specified DOCUMENT root location. The name of the component will be added to this root path.'
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
        if (!empty($options['document'])) {
            $this->requirePackageXml($config->getPackageDirectory());
            $this->_dependencies->getRunnerDocument()->run();
        }
    }
}
