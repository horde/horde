<?php

require_once __DIR__ . '/InterceptedException.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_InterceptingParser extends Horde_Argv_Parser
{
    public function parserExit($status = 0, $msg = null)
    {
        throw new Horde_Argv_InterceptedException(null, $status, $msg);
    }

    public function parserError($msg)
    {
        throw new Horde_Argv_InterceptedException($msg);
    }

}
