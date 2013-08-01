<?php
/**
 * Horde_Crypt_Pgp tests involving the keyserver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */

class Horde_Crypt_PgpKeyserverTest extends Horde_Test_Case
{
    protected $_pgp;

    protected function setUp()
    {
        $config = self::getConfig('CRYPT_TEST_CONFIG');
        if (!$config || empty($config['crypt']['keyserver'])) {
            $this->markTestSkipped('Keyserver test has not been enabled.');
        }

        if (!is_executable('/usr/bin/gpg')) {
            $this->markTestSkipped('GPG binary not found at /usr/bin/gpg.');
        }

        $this->_pgp = Horde_Crypt::factory('Pgp', array(
            'program' => '/usr/bin/gpg',
            'temp' => sys_get_temp_dir()
        ));
    }

    public function testKeyserverRetrieve()
    {
        $this->_pgp->getPublicKeyserver('4DE5B969');
    }

    public function testKeyserverRetrieveByEmail()
    {
        $this->assertEquals(
            '4DE5B969',
            $this->_pgp->getKeyID('jan@horde.org')
        );
    }

}
