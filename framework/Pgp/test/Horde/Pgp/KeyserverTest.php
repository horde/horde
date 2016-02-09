<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Tests for accessing a public PGP keyserver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_KeyserverTest extends Horde_Test_Case
{
    protected $_ks;

    protected function setUp()
    {
        $this->_ks = new Horde_Pgp_Keyserver(array(
            'keyserver' => 'http://ha.pool.sks-keyservers.net:11371'
        ));
    }

    /**
     * @dataProvider keyserverRetrieveProvider
     */
    public function testKeyserverRetrieve($id)
    {
        try {
            $this->_checkKey($this->_ks->get($id), $id);
        } catch (Horde_Pgp_Exception $e) {
            if ($e->getPrevious() instanceof Horde_Http_Exception) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function keyserverRetrieveProvider()
    {
        return array(
            array('4DE5B969')
        );
    }

    /**
     * @dataProvider keyserverRetrieveByEmailProvider
     */
    public function testKeyserverRetrieveByEmail($email, $id)
    {
        try {
            $this->_checkKey($this->_ks->getKeyByEmail($email), $id);
        } catch (Horde_Pgp_Exception $e) {
            if ($e->getPrevious() instanceof Horde_Http_Exception) {
                $this->markTestSkipped($e->getPrevious()->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function keyserverRetrieveByEmailProvider()
    {
        return array(
            array('jan@horde.org', '4DE5B969')
        );
    }

    protected function _checkKey($key, $id)
    {
        $this->assertInstanceOf(
            'Horde_Pgp_Element_PublicKey',
            $key
        );

        $this->assertEquals(
            $id,
            $key->id
        );
    }

}
