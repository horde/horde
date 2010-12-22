<?php
/**
 * Command line tools for Kolab storage.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Command line tools for Kolab storage.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli
{
    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     * <pre>
     * 'parser'   - (array)     Parser configuration parameters.
     *   'class'  - (string)    The class name of the parser to use.
     * 'output'   - (Horde_Cli) The output handler.
     * </pre>
     */
    static public function main(array $parameters = array())
    {
        $modular = self::_prepareModular($parameters);
        if (empty($parameters['output'])) {
            if (!class_exists('Horde_Cli')) {
                throw new Horde_Kolab_Cli_Exception('The Horde_Cli package seems to be missing (Class Horde_Cli is missing)!');
            }
            $cli = Horde_Cli::init();
        } else {
            $cli = $parameters['output'];
        }
        $parser = $modular->createParser();
        list($options, $arguments) = $parser->parseArgs();
        if (count($arguments) == 0) {
            $parser->printHelp();
        } else {
            try {
                $modular->getProvider()
                    ->getModule(ucfirst($arguments[0]))
                    ->run($options, $arguments);
            } catch (Horde_Cli_Modular_Exception $e) {
                $parser->printHelp();
            }
        }
    }

    static private function _prepareModular(array $parameters = array())
    {
        return new Horde_Cli_Modular(
            array(
                'parser' => array(
                    'class' => empty($parameters['parser']['class']) ? 'Horde_Argv_Parser' : $parameters['parser']['class'],
                    'usage' => Horde_Kolab_Cli_Translation::t(
                        "[options] MODULE ACTION\n\nPossible MODULEs and ACTIONs:\n\n"
                    )
                ),
                'modules' => array(
                    'directory' => dirname(__FILE__) . '/Cli/Module',
                ),
                'provider' => array(
                    'prefix' => 'Horde_Kolab_Cli_Module_'
                )
            )
        );
    }
}