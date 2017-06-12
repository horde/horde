<?php
/**
 * Horde_Crypt_Smime tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */

class Horde_Crypt_SmimeTest extends Horde_Test_Case
{
    protected function setUp()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('No openssl support in PHP.');
        }
    }

    public function testSubjectAltName()
    {
        $smime = Horde_Crypt::factory('Smime', array(
            'temp' => sys_get_temp_dir()
        ));

        $key = file_get_contents(
            __DIR__ . '/fixtures/smime_subjectAltName.pem'
        );

        $this->assertEquals(
            'test1@example.com',
            $smime->getEmailFromKey($key)
        );
    }

    public function testExtractSignedContent()
    {
        $smime = Horde_Crypt::factory('Smime', array(
            'temp' => sys_get_temp_dir()
        ));
        $message = file_get_contents(
            __DIR__ . '/fixtures/smime_signed_opaque.eml'
        );
        $expected = "Content-Type: text/plain\r\n\r\nHello World!\r\n";
        $this->assertEquals(
            $expected,
            $smime->extractSignedContents($message)
        );
    }
}
