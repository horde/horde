<?php
/**
 * The command line handling for the Kolab_Filter package.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * The command line handling for the Kolab_Filter package.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Cli
{
    /**
     * The CLI argument parser.
     *
     * @var Horde_Argv_Parser
     */
    private $_parser;

    /**
     * The CLI options.
     *
     * @var Horde_Argv_Values
     */
    private $_options;

    /**
     * The CLI arguments.
     *
     * @var array
     */
    private $_arguments;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_parser = new Horde_Kolab_Filter_Cli_Parser(
            array('optionList' =>
                  array(
                      new Horde_Argv_Option(
                          '-s',
                          '--sender',
                          array(
                              'help' => 'The message sender.',
                              'type' => 'string',
                              'nargs' => 1
                          )
                      ),
                      new Horde_Argv_Option(
                          '-r',
                          '--recipient',
                          array(
                              'help' => 'A message recipient.',
                              'action' => 'append',
                              'type' => 'string'
                          )
                      ),
                      new Horde_Argv_Option(
                          '-H',
                          '--host',
                          array(
                              'help' => 'The host running this script.'
                          )
                      ),
                      new Horde_Argv_Option(
                          '-c',
                          '--client',
                          array(
                              'help' => 'The client sending the message.'
                          )
                      ),
                      new Horde_Argv_Option(
                          '-u',
                          '--user',
                          array(
                              'help' => 'ID of the currently authenticated user.',
                              'default' => ''
                          )
                      ),
                      new Horde_Argv_Option(
                          '-C',
                          '--config',
                          array(
                              'help' => 'Path to the configuration file for this filter.'
                          )
                      )
                  )
            )
        );
    }

    /**
     * Parse the command line arguments.
     *
     * @return NULL
     */
    public function parse()
    {
        try {
            list($this->_options, $this->_arguments) = $this->_parser->parseArgs();
        } catch (InvalidArgumentException $e) {
            throw new Horde_Kolab_Filter_Exception_Usage(
                $e->getMessage() . "\n\n" . $this->_parser->getUsage()
            );
        }

        if (empty($this->_options['recipient'])) {
            throw new Horde_Kolab_Filter_Exception_Usage(
                sprintf(
                    "Please provide one or more recipients.\n\n%s",
                    $this->_parser->getUsage()
                )
            );
        }
    }

    /**
     * Return the command line options.
     *
     * @return Horde_Argv_Values The command line values.
     */
    public function getOptions()
    {
        if ($this->_options === null) {
            $this->parse();
        }
        return $this->_options;
    }
}