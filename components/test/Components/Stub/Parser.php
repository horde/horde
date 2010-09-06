<?php
class Components_Stub_Parser
extends Horde_Argv_Parser
{
    public function parserExit($status = 0, $msg = null)
    {
        if ($msg)
            fwrite(STDERR, $msg);
    }
}