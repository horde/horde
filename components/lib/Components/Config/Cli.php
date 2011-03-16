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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Config_Cli:: class provides central options for the command line
 * configuration of the components tool.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
                    'help'   => 'Just pretend and indicate what would be done rather than performing the action (This option is not yet supported by all modules - "install" and "release" so far).',
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
                '-R',
                '--pearrc',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the configuration of the PEAR installation you want to use for all PEAR based actions (leave empty to use your system default PEAR environment).'
                )
            )
        );

        list($this->_options, $this->_arguments) = $this->_parser->parseArgs();
    }
}
