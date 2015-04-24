<?php
/**
 * Horde Mapi_Utils tests.
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mapi_Utils
 * @subpackage UnitTests
 */

/**
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mapi_Utils
 * @subpackage UnitTests
 */
class Horde_Mapi_MapiTest extends PHPUnit_Framework_TestCase
{

    public function testFiletimeToUnixTime()
    {
        $data = file_get_contents(__DIR__ . '/fixtures/filetime');

        $this->assertEquals(
            extension_loaded('bcmath') ? 1387818000 : -1,
            Horde_Mapi::filetimeToUnixtime($data)
        );
    }

    /**
     * Test parsing GOID value.
     */
    public function testParseGlobalObjectId()
    {
        // Outlook UID
        $fixture = 'BAAAAIIA4AB0xbcQGoLgCAfUCRDgQMnBJoXEAQAAAAAAAAAAEAAAAAvw7UtuTulOnjnjhns3jvM=';
        $uid = Horde_Mapi::getUidFromGoid($fixture);
        $this->assertEquals(
          '040000008200E00074C5B7101A82E00800000000E040C9C12685C4010000000000000000100000000BF0ED4B6E4EE94E9E39E3867B378EF3',
          $uid);

        // vCal
        $fixture = 'BAAAAIIA4AB0xbcQGoLgCAAAAAAAAAAAAAAAAAAAAAAAAAAAMwAAAHZDYWwtVWlkAQAAAHs4MTQxMkQzQy0yQTI0LTRFOUQtQjIwRS0xMUY3QkJFOTI3OTl9AA==';
        $uid = Horde_Mapi::getUidFromGoid($fixture);
        $this->assertEquals('{81412D3C-2A24-4E9D-B20E-11F7BBE92799}', $uid);
    }

}
