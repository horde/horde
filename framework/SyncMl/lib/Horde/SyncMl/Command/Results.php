<?php
/**
 * The Horde_SyncMl_Command_Results class provides a SyncML implementation of
 * the Results command as defined in SyncML Representation Protocol, version
 * 1.1, section 5.5.12.
 *
 * The Results command is used to return the results of a Search or Get
 * command. Currently Horde_SyncMl_Command_Results behaves the same as
 * Horde_SyncMl_Command_Put. The only results we get is the same DevInf as for
 * the Put command.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Nathan P Sharp
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command_Results extends Horde_SyncMl_Command_Put
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'Results';
}
