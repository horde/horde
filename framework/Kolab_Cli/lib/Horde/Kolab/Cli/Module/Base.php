<?php
/**
 * The Horde_Kolab_Cli_Module_Base:: module provides the base options of the
 * Kolab CLI.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * The Horde_Kolab_Cli_Module_Base:: module provides the base options of the
 * Kolab CLI.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Kolab_Cli_Module_Base
implements Horde_Cli_Modular_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '';
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-d',
                '--driver',
                array(
                    'action' => 'store',
                    'choices' => array('horde', 'horde-php', 'php', 'pear', 'roundcube', 'mock'),
                    'help'   => Horde_Kolab_Cli_Translation::t(
"The Kolab backend driver that should be used.
Choices are:

 - horde     [IMAP]: The Horde_Imap_Client driver as pure PHP implementation.
 - horde-php [IMAP]: The Horde_Imap_Client driver based on c-client in PHP
 - php       [IMAP]: The PHP imap_* functions which are based on c-client
 - pear      [IMAP]: The PEAR-Net_IMAP driver
 - roundcube [IMAP]: The roundcube IMAP driver
 - mock      [Mem.]: A dummy driver that uses memory."
                    )
                )
            ),
            new Horde_Argv_Option(
                '-u',
                '--username',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--password',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The password of the user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-H',
                '--host',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The host that holds the data.')
                )
            ),
        );
    }

    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return false;
    }

    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return '';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return '';
    }

    /**
     * Return the options for this module.
     *
     * @return array The group options.
     */
    public function getOptionGroupOptions()
    {
        return array();
    }
}