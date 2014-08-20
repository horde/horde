<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Timezone
 * @subpackage UnitTests
 */
class Horde_Timezone_ParseTest extends Horde_Test_Case
{
    public function testBug13455()
    {
        $tz = new Horde_Timezone_Mock('europe');
        $tz->getZone('Europe/Dublin')->toVtimezone();
    }
}
