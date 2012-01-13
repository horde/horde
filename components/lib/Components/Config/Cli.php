<?php
/**
 * Components_Config_Cli:: class provides central options for the command line
 * configuration of the components tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Config_Cli:: class provides central options for the command line
 * configuration of the components tool.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Config_Cli
extends Components_Config_Base
{
    /**
     * The command line argument parser.
     *
     * @var Horde_Argv_Parser
     */
    private $_parser;

    /**
     * Constructor.
     *
     */
    public function __construct(
        Horde_Argv_Parser $parser
    ) {
        $this->_parser = $parser;

        $parser->addOption(
            new Horde_Argv_Option(
                '-c',
                '--config',
                array(
                    'action' => 'store',
                    'help'   => sprintf(
                        'the path to the configuration file for the components script (default : %s).',
                        Components_Constants::getConfigFile()
                    ),
                    'default' => Components_Constants::getConfigFile()
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-q',
                '--quiet',
                array(
                    'action' => 'store_true',
                    'help'   => 'Reduce output to a minimum'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-v',
                '--verbose',
                array(
                    'action' => 'store_true',
                    'help'   => 'Reduce output to a maximum'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-P',
                '--pretend',
                array(
                    'action' => 'store_true',
                    'help'   => 'Just pretend and indicate what would be done rather than performing the action.',
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-N',
                '--nocolor',
                array(
                    'action' => 'store_true',
                    'help'   => 'Avoid colors in the output'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-t',
                '--templatedir',
                array(
                    'action' => 'store',
                    'help'   => 'Location of a template directory that contains template definitions (see the data directory of this package to get an impression of which templates are available).'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-D',
                '--destination',
                array(
                    'action' => 'store',
                    'help'   => 'Path to an (existing) destination directory where any output files will be placed.'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-R',
                '--pearrc',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the configuration of the PEAR installation you want to use for all PEAR based actions (leave empty to use your system default PEAR environment).'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '--allow-remote',
                array(
                    'action' => 'store_true',
                    'help'   => 'allow horde-components to access the remote http://pear.horde.org for dealing with stable releases. This option is not required in case you work locally in your git checkout and will only work for some actions that are able to operate on stable release packages.'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-G',
                '--commit',
                array(
                    'action' => 'store_true',
                    'help'   => 'Commit any changes during the selected action to git.'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '--horde-root',
                array(
                    'action' => 'store',
                    'help'   => 'The root of the Horde git repository.'
                )
            )
        );
        list($this->_options, $this->_arguments) = $this->_parser->parseArgs();
    }
}
