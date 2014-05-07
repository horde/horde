<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_ObjectTest extends Horde_Alarm_Storage_Base
{
    public function testFactory()
    {
        self::$alarm = new Horde_Alarm_Object();
    }
}
