<?php
/**
 * Creates the Horde_Push content object.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */

/**
 * Creates the Horde_Push content object.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL-2.0). If you did
 * not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */
class Horde_Push_Cli_Factory_Push
{
    /**
     * Create the Horde_Push content element.
     *
     * @param array $arguments The command line arguments.
     * @param array $options   Command line options.
     * @param array $conf      The configuration.
     */
    public function create($arguments, $options, $conf)
    {
        return new Horde_Push();
    }
}