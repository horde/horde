<?php
/**
 * Ingo_Storage_Vacation_Test overrides certain Ingo_Storage_Vacation
 * functionality to help with unit testing.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
