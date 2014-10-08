<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Package testing on a real (live) IMAP server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Live_Pop3Test extends Horde_Test_Case
{
    /**
     * Add the tests to the current test runner.
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite;

        $c = self::getConfig('IMAPCLIENT_TEST_CONFIG_POP3', __DIR__ . '/../');
        if (!is_null($c) && !empty($c['pop3client'])) {
            foreach ($c['pop3client'] as $val) {
                if (!empty($val['enabled']) &&
                    !empty($val['client_config']['username']) &&
                    !empty($val['client_config']['password'])) {
                    Horde_Imap_Client_Live_Pop3::$config[] = $val;
                    $suite->addTestSuite('Horde_Imap_Client_Live_Pop3');
                }
            }
        }

        return $suite;
    }

}
