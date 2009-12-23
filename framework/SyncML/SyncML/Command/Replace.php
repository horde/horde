<?php

require_once 'SyncML/Command.php';

/**
 * The SyncML_Command_Replace class provides a SyncML implementation of the
 * Replace command as defined in SyncML Representation Protocol, version 1.1,
 * section 5.5.11.
 *
 * The Replace command is used to replace data on the recipient device.
 *
 * $Horde: framework/SyncML/SyncML/Command/Replace.php,v 1.19 2009/01/06 17:49:50 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @author  Jan Schneider <jan@horde.org>
 * @since   Horde 3.0
 * @package SyncML
 */
class SyncML_Command_Replace extends SyncML_Command {

    /**
     * Name of the command.
     *
     * @var string
     */
    var $_cmdName = 'Replace';

}
