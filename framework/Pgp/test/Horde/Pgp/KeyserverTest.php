<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Tests for accessing a public PGP keyserver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
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
        $this->_ks = new Horde_Pgp_Keyserver();
    }

    /**
     * @dataProvider keyserverRetrieveProvider
     */
    public function testKeyserverRetrieve($id)
    {
        try {
            $key = $this->_ks->get($id);
            if (is_null($key)) {
                $this->markTestSkipped('Error retrieving key from keyserver');
            } else {
                $this->_checkKey($this->_ks->get($id), $id);
            }
        } catch (Horde_Pgp_Exception $e) {
            /* Ignore all exceptions. Keyserver retrieval is not 100%
             * reliable. */
            $this->markTestSkipped($e->getMessage());
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
            /* Ignore all exceptions. Keyserver retrieval is not 100%
             * reliable. */
            $this->markTestSkipped($e->getMessage());
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
