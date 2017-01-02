<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */

/**
 * Logs to the command line interface using Horde_Cli.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class Horde_Log_Handler_Cli extends Horde_Log_Handler_Stream
{
    /**
     * A CLI handler.
     *
     * @var Horde_Cli
     */
    protected $_cli;

    /**
     * Class Constructor
     *
     * @param Horde_Log_Formatter $formatter  Log formatter.
     */
    public function __construct(Horde_Log_Formatter $formatter = null)
    {
        $this->_cli = new Horde_Cli();
        $this->_formatter = is_null($formatter)
            ? new Horde_Log_Formatter_Cli($this->_cli)
            : $formatter;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event  Log event.
     *
     * @return boolean  True.
     * @throws Horde_Log_Exception
     */
    public function write($event)
    {
        $this->_cli->writeln($this->_formatter->format($event));
        return true;
    }
}
