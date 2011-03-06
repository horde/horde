<?php
/**
 * The Horde_SyncMl_Command class provides a base class for handling all
 * <SyncBody> commands.
 *
 * A SyncML command is a protocol primitive. Each SyncML command specifies to a
 * recipient an individual operation that is to be performed.
 *
 * The Horde_SyncMl_Command objects are hooked into the XML parser of the
 * Horde_SyncMl_ContentHandler class and are reponsible for parsing a single
 * command inside the SyncBody section of a SyncML message. All actions that
 * must be executed for a single SyncML command are handled by these objects,
 * by means of the handleCommand() method.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command
{
    /**
     * Name of the command, like 'Put'.
     *
     * Must be overwritten by a sub class.
     *
     * @var string
     */
    protected $_cmdName;

    /**
     * The command ID (<CmdID>).
     *
     * @var integer
     */
    protected $_cmdID;

    /**
     * Stack for holding the XML elements during creation of the object from
     * the XML event flow.
     *
     * @var array
     */
    protected $_stack = array();

    /**
     * Buffer for the parsed character data.
     *
     * @var string
     */
    protected $_chars = '';

    /**
     * A Horde_SyncMl_XmlOutput instance responsible for generating the output.
     *
     * @var Horde_SyncMl_XmlOutput
     */
    protected $_outputHandler;

    /**
     * Constructor.
     *
     * @param Horde_SyncMl_XmlOutput $outputHandler  A Horde_SyncMl_XmlOutput object.
     */
    public function __construct(&$outputHandler)
    {
        $this->_outputHandler = &$outputHandler;
    }

    /**
     * Start element handler for the XML parser, delegated from
     * Horde_SyncMl_ContentHandler::startElement().
     *
     * @param string $uri      The namespace URI of the element.
     * @param string $element  The element tag name.
     * @param array $attrs     A hash with the element's attributes.
     */
    public function startElement($uri, $element, $attrs)
    {
        $this->_stack[] = $element;
    }

    /**
     * End element handler for the XML parser, delegated from
     * Horde_SyncMl_ContentHandler::endElement().
     *
     * @param string $uri      The namespace URI of the element.
     * @param string $element  The element tag name.
     */
    public function endElement($uri, $element)
    {
        if (count($this->_stack) == 2 &&
            $element == 'CmdID') {
            $this->_cmdID = intval(trim($this->_chars));
        }

        if (strlen($this->_chars)) {
            $this->_chars = '';
        }

        array_pop($this->_stack);
    }

    /**
     * Character data handler for the XML parser, delegated from
     * Horde_SyncMl_ContentHandler::characters().
     *
     * @param string $str  The data string.
     */
    public function characters($str)
    {
        $this->_chars .= $str;
    }

    /**
     * Returns the command name this instance is reponsible for.
     *
     * @return string  The command name this object is handling.
     */
    public function getCommandName()
    {
        return $this->_cmdName;
    }

    /**
     * This method is supposed to implement the actual business logic of the
     * command once the XML parsing is complete.
     *
     * @abstract
     */
    public function handleCommand($debug = false)
    {
    }

    /**
     * Attempts to return a concrete Horde_SyncMl_Command instance based on
     * $command.
     *
     * @param string $command                  The type of the concrete
     *                                         Horde_SyncMl_Comment subclass to
     *                                         return.
     * @param Horde_SyncMl_XmlOutput $outputHandler  A Horde_SyncMl_XmlOutput object.
     *
     * @return Horde_SyncMl_Command  The newly created concrete Horde_SyncMl_Command
     *                         instance, or false on error.
     */
    public function &factory($command, &$outputHandler)
    {
        $command = basename($command);
        $class = 'Horde_SyncMl_Command_' . $command;
        if (class_exists($class)) {
            $cmd = new $class($outputHandler);
        } else {
            $msg = 'Class definition of ' . $class . ' not found.';
            $GLOBALS['backend']->logMessage($msg, __FILE__, __LINE__, 'ERR');
            $cmd = PEAR::raiseError($msg);
        }

        return $cmd;
    }
}
