<?php
/**
 * The Ingo_Script_Sieve_Action_Removeflag class represents a remove flag
 * action.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Removeflag extends Ingo_Script_Sieve_Action_Flag
{
    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return $this->_toCode('removeflag');
    }

}
