<?php
/**
 * Tests for accessing a public PGP keyserver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */

class Horde_Crypt_PgpKeyserverTest extends Horde_Test_Case
{
    protected $_ks;

    protected function setUp()
    {
        $config = self::getConfig('CRYPT_TEST_CONFIG');
        if (!$config || empty($config['crypt']['gnupg'])) {
            $this->markTestSkipped('Keyserver test has not been enabled.');
        }

        if (!is_executable($config['crypt']['gnupg'])) {
            $this->markTestSkipped('GPG binary not found.');
        }

        $this->_ks = new Horde_Crypt_Pgp_Keyserver(
            Horde_Crypt::factory('Pgp', array(
                'program' => $config['crypt']['gnupg'],
                'temp' => sys_get_temp_dir()
            ))
        );
    }

    public function testKeyserverRetrieve()
    {
        $this->_ks->get('4DE5B969');
    }

    public function testKeyserverRetrieveByEmail()
    {
        $this->assertEquals(
            '4DE5B969',
            $this->_ks->getKeyID('jan@horde.org')
        );
    }

}
