<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */

/**
 * Formatter for the command line interface using Horde_Cli.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class Horde_Log_Formatter_Cli implements Horde_Log_Formatter
{
    /**
     * A CLI handler.
     *
     * @var Horde_Cli
     */
    protected $_cli;

    /**
     * Constructor.
     *
     * @param Horde_Cli $cli  A Horde_Cli instance.
     */
    public function __construct(Horde_Cli $cli)
    {
        $this->_cli = $cli;
    }

    /**
     * Formats an event to be written by the handler.
     *
     * @param array $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format($event)
    {
        $flag = '['. str_pad($event['levelName'], 7, ' ', STR_PAD_BOTH) . '] ';

        switch ($event['level']) {
        case Horde_Log::EMERG:
        case Horde_Log::ALERT:
        case Horde_Log::CRIT:
        case Horde_Log::ERR:
            $type_message = $this->_cli->red($flag);
            break;

        case Horde_Log::WARN:
        case Horde_Log::NOTICE:
            $type_message = $this->_cli->yellow($flag);
            break;

        case Horde_Log::INFO:
        case Horde_Log::DEBUG:
            $type_message = $this->_cli->blue($flag);
            break;

        default:
            $type_message = $flag;
        }

        return $type_message . $event['message'];
    }

}
