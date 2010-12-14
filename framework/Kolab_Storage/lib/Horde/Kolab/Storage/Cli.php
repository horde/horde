<?php
/**
 * Command line tools for Kolab storage.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
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
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Cli
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
            if (!class_exists('Horde_Cli')) {
                throw new Horde_Kolab_Storage_Exception('The Horde_Cli package seems to be missing (Class Horde_Cli is missing)!');
            }
            $cli = Horde_Cli::init();
        } else {
            $cli = $parameters['output'];
        }
        list($options, $arguments) = $parser->parseArgs();
        if (count($arguments) == 0) {
            $parser->printHelp();
        } else {
            switch ($arguments[0]) {
            case 'folder':
                $factory = new Horde_Kolab_Storage_Factory();
                var_dump(
                    $factory->createFromParams(
                        array(
                            'driver' => $options['driver'],
                            'params' => $options
                        )
                    )->listFolders()
                );
                break;
            default:
                $parser->printHelp();
            }
        }
    }

    static private function _prepareParser(array $parameters = array())
    {
        if (empty($parameters['parser']['class'])) {
            $parser_class = 'Horde_Argv_Parser';
        } else {
            $parser_class = $parameters['parser']['class'];
        }
        $options = array(
            new Horde_Argv_Option(
                '-d',
                '--driver',
                array(
                    'action' => 'store',
                    'choices' => array('horde', 'horde-php', 'php', 'pear', 'roundcube', 'mock'),
                    'help'   => Horde_Kolab_Storage_Translation::t(
"The Kolab backend driver that should be used.
Choices are:

 - horde     [IMAP]: The Horde_Imap_Client driver as pure PHP implementation.
 - horde-php [IMAP]: The Horde_Imap_Client driver based on c-client in PHP
 - php       [IMAP]: The PHP imap_* functions which are based on c-client
 - pear      [IMAP]: The PEAR-Net_IMAP driver
 - roundcube [IMAP]: The roundcube IMAP driver
 - mock    [Memory]: A dummy driver."
                    )
                )
            ),
            new Horde_Argv_Option(
                '-u',
                '--username',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Storage_Translation::t('The user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--password',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Storage_Translation::t('The password of the user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-H',
                '--host',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Storage_Translation::t('The host that holds the data.')
                )
            ),
        );
        $usage = Horde_Kolab_Storage_Translation::t(
            "[options] MODULE ACTION\nPossible MODULEs and ACTIONs:

  folder - Handle folders.
    list [default] - List the folders
"
        );
        return new $parser_class(
            array(
                'usage' => '%prog ' . $usage,
                'optionList' => $options
            )
        );
    }
}