<?php
/**
 * Command line tools for Kolab format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Command line tools for Kolab format.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Cli
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
        $parser = self::_prepareParser($parameters);
        if (empty($parameters['output'])) {
            $cli = Horde_Cli::init();
        } else {
            $cli = $parameters['output'];
        }
        list($options, $arguments) = $parser->parseArgs();
        $factory = new Horde_Kolab_Format_Factory();
        $timed = $factory->createTimed('xml', 'task');
        for ($i = 0; $i < 1000; $i++) {
            $timed->load(
                file_get_contents($arguments[0])
            );
        }
        $cli->message($timed->timeSpent());
    }

    static private function _prepareParser(array $parameters = array())
    {
        if (empty($parameters['parser']['class'])) {
            $parser_class = 'Horde_Argv_Parser';
        } else {
            $parser_class = $parameters['parser']['class'];
        }
        return new $parser_class(
            array(
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );
    }
}