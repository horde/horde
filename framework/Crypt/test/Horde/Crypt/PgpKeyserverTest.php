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
    protected $_gnupg;

    protected function setUp()
    {
        $c = self::getConfig('CRYPT_TEST_CONFIG', __DIR__);
        $this->_gnupg = isset($c['gnupg'])
            ? $c['gnupg']
            : '/usr/bin/gpg';

        if (!is_executable($this->_gnupg)) {
            $this->markTestSkipped(sprintf(
                'GPG binary not found at %s.',
                $this->_gnupg
            ));
        }

        $this->_ks = new Horde_Crypt_Pgp_Keyserver(
            Horde_Crypt::factory('Pgp', array(
                'program' => $this->_gnupg
            ))
        );
    }

    public function testKeyserverRetrieve()
    {
        try {
            $this->_ks->get('4DE5B969');
        } catch (Horde_Crypt_Exception $e) {
            if ($e->getPrevious() instanceof Horde_Http_Exception) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testKeyserverRetrieveByEmail()
    {
        try {
            $this->assertEquals(
                '4DE5B969',
                $this->_ks->getKeyID('jan@horde.org')
            );
        } catch (Horde_Crypt_Exception $e) {
            if ($e->getPrevious() instanceof Horde_Http_Exception) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testBrokenKeyserver()
    {
        $ks = new Horde_Crypt_Pgp_Keyserver(
            Horde_Crypt::factory('Pgp', array(
                'program' => $this->_gnupg
            )),
            array('keyserver' => 'http://pgp.key-server.io')
        );
        try {
            $this->assertEquals(
                '4DE5B969',
                $ks->getKeyID('jan@horde.org')
            );
        } catch (Horde_Crypt_Exception $e) {
            if ($e->getPrevious() instanceof Horde_Http_Exception) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
