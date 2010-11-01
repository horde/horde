<?php
/**
 * Ingo_Storage_Vacation_Test overrides certain Ingo_Storage_Vacation
 * functionality to help with unit testing.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_VacationTest extends Ingo_Storage_Vacation
{
    /**
     */
    public function getVacationAddresses()
    {
        return $this->_addr;
    }

}
