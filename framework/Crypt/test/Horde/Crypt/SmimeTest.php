<?php
/**
 * Horde_Crypt_Smime tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Crypt
 * @subpackage UnitTests
 */

class Horde_Crypt_SmimeTest extends PHPUnit_Framework_TestCase
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
            'temp' => Horde_Util::getTempDir()
        ));

        $key = file_get_contents(dirname(__FILE__) . '/fixtures/smime_subjectAltName.pem');

        $this->assertEquals(
            'test1@example.com',
            $smime->getEmailFromKey($key)
        );
    }

}
