<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */

/**
 * Test for the Messagepack (serialized methods) driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */
class Horde_Pack_Driver_MsgpackserializeTest
extends Horde_Pack_Driver_TestBase
{
    protected $drivername = 'Horde_Pack_Driver_Msgpackserialize';
}
