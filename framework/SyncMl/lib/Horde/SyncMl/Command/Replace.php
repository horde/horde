<?php
/**
 * The Horde_SyncMl_Command_Replace class provides a SyncML implementation of
 * the Replace command as defined in SyncML Representation Protocol, version
 * 1.1, section 5.5.11.
 *
 * The Replace command is used to replace data on the recipient device.
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
class Horde_SyncMl_Command_Replace extends Horde_SyncMl_Command
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'Replace';
}
