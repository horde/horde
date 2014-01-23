<?php
/**
 * The Cli:: class is the entry point for the various cli actions
 * provided by horde-cli.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Horde
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Horde
 */

/**
 * The AdminCli:: class is the entry point for the various cli actions
 * provided by horde-cli.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Horde
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Horde
 */
class AdminCli
{

    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     * <pre>
     * 'cli'        - (array)  CLI configuration parameters.
     *   'parser'   - (array)  Parser configuration parameters.
     *     'class'  - (string) The class name of the parser to use.
     * </pre>
     */
    static public function main(array $parameters = array())
    {
        $modular = new Horde_Cli_Modular(
          array(
            'parser' => array(
               'class' => empty($parameters['parser']['class']) ? 'Horde_Argv_Parser' : $parameters['parser']['class'],
               'usage' => "[options] [COMMAND]\n"),
            'modules' => array(
                    'directory' => __DIR__ . '/AdminCli',
                    'exclude' => 'Base'
                ),
            'provider' => array('prefix' => 'AdminCli_Module_')
                )
        );
        $modules = $modular->getModules();
        $parser = $modular->createParser();
        list($options, $arguments) = $parser->parseArgs();

        // get the provider by the first unparsed argument
        if (count($arguments) == 0) {
            $parser->printHelp();
            exit;
        }
        $moduleName = $arguments[0];
        $provider = $modular->getProvider();
        try {
            $module = $provider->getModule($moduleName);
        } catch (Horde_Cli_Modular_Exception $e) {
            print "Invalid Module\n";
            $parser->printHelp();
            exit;
        }
        $module->run($parameters['cli'], $options, $arguments);
    }
}
