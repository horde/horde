<?php
/**
 * Base for testing.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */
class Horde_Auth_TestCase extends Horde_Test_Case
{
    public function getCredentials()
    {
        return array(
            array('aprmd5', '$apr1$11CBbKXP$AvvMGBjr81bC/NSMZIxrG.', '11CBbKXP'),
            array('crypt', '8e3IWstJmsmxs', '8e'),
            array('crypt-blowfish', '$2a$08$xJOYeQ7koDohq69yZeFXNO5TvrTlFLs4NP2..EcZP57oeOVgXoxqy', '$2a$08$xJOYeQ7koDohq69yZeFXNO'),
            array('crypt-des', '45MibW6/G3XEY', '45'),
            array('crypt-md5', '$1$537a3a0e$CWyLVJdQKfxbKPBv/Efzm0', '$1$537a3a0e$'),
            array('md5-base64', 'OFj2IjCsPJFfMAxmQxLGPw==', ''),
            array('md5-hex', '3858f62230ac3c915f300c664312c63f', ''),
            array('msad', "\"\0f\0o\0o\0b\0a\0r\0\"\0", ''),
            array('mysql', '*9B500343BC52E2911172EB52AE5CF4847604C6E5', ''),
            array('plain', 'foobar', ''),
            array('sha', 'iEPX+SQWIR3p67lj/0zigSWTKHg=', ''),
            array('smd5', 'ISCNJwzwP30CadahjpkbL2l6bHJxd2h2', 'izlrqwhv'),
            array('smd5', '{SMD5}ISCNJwzwP30CadahjpkbL2l6bHJxd2h2', 'izlrqwhv', true),
            array('smd5', 'bn3EnZ0TFc+yyx3KotqS5GlydmM=', 'irvc'),
            array('smd5', 'GZ4KWKk2W6eSOHjVXLhOOzADuwA=', hex2bin('3003bb00')),
            array('smd5', '6y2n+CGCZhuB32dyFu3keQtY0Vc=', hex2bin('0b58d157')),
            array('ssha', 'buQrQ9vazjrHtO6oIfSZhSBjVxdjemZvZHVubg==', 'czfodunn'),
            array('ssha', 'BLDmpxHYTH2/Bmg4veVfbglU68jQKEuK', hex2bin('d0284b8a')),
            array('ssha', '2iXr83rPabLxmrx7uulT4W7mJFrawT41', hex2bin('dac13e35')),
            array('ssha', '{SSHA}6IeOcols85dHJeTHevA356ruftrA2PRX', hex2bin('c0d8f457'), true),
            array('ssha', '6IeOcols85dHJeTHevA356ruftrA2PRX', hex2bin('c0d8f457')),
            array('ssha256', '{SSHA256}wnD9GBo+WIXZ+bVD7DjoDokQBjkVgtufXyBh1EqfXn11+sUG', hex2bin('75fac506'), true),
            array('ssha256', 'wnD9GBo+WIXZ+bVD7DjoDokQBjkVgtufXyBh1EqfXn11+sUG', hex2bin('75fac506')),
        );
    }
}
