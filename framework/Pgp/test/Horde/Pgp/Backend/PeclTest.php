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
 * Tests for the Gnupg PECL backend.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_Backend_PeclTest
extends Horde_Pgp_Backend_TestBase
{
    protected function _setUp()
    {
        if (!Horde_Pgp_Backend_Pecl::supported()) {
            $this->markTestSkipped('gnupg PECL extension not available');
        }

        return array(new Horde_Pgp_Backend_Pecl());
    }

    /**
     * @dataProvider generateKeyProvider
     */
    public function testGenerateKey($passphrase)
    {
        $this->markTestSkipped(
            'PECL extension does not support key generation'
        );
    }

    public function testEncryptSymmetric()
    {
        $this->markTestSkipped(
            'PECL extension does not support symmetric encryption'
        );
    }

    public function testDecryptSymmetric()
    {
        $this->markTestSkipped(
            'PECL extension does not support symmetric encryption'
        );
    }

}
