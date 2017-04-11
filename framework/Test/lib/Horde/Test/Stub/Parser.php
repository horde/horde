<?php
/**
 * A test helper for testing Horde_Argv based classes.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * A test helper for testing Horde_Argv based classes.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Stub_Parser
extends Horde_Argv_Parser
{
    public function __construct($args = array())
    {
        $args['formatter'] = new Horde_Argv_IndentedHelpFormatter(
            2, 14, null, true,
            new Horde_Cli_Color(Horde_Cli_Color::FORMAT_NONE)
        );
        parent::__construct($args);
    }

    /**
     * Print a usage message incorporating $msg to stderr and exit.
     * If you override this in a subclass, it should not return -- it
     * should either exit or raise an exception.
     *
     * @param string $msg
     */
    public function parserError($msg)
    {
        $this->printUsage();
        $this->parserExit(2, sprintf("%s: error: %s\n", $this->getProgName(), $msg));
    }

    public function parserExit($status = 0, $msg = null)
    {
        if ($msg) {
            echo $msg;
        }
    }
}
