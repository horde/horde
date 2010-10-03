<?php
class Components_Stub_Parser
extends Horde_Argv_Parser
{
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
        if ($msg)
            echo $msg;
    }
}