<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Timezone
 * @subpackage UnitTests
 */

class Horde_Timezone_IcalendarTest extends Horde_Test_Case
{
    public function testSomething()
    {
        $tz = new Horde_Timezone_Mock();
        $this->assertStringEqualsFile(
            dirname(__FILE__) . '/fixtures/europe.ics',
            $tz->getZone('Europe/Jersey')->toVtimezone()->exportVcalendar()
        );
    }
}

class Horde_Timezone_Mock extends Horde_Timezone
{
    protected function _download()
    {
    }

    protected function _extractAndParse()
    {
        $this->_parse(file_get_contents(dirname(__FILE__) . '/fixtures/europe'));
    }
}
