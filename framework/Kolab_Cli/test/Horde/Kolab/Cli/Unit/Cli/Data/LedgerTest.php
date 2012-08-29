<?php
/**
 * Test the ledger data handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the ledger data handler.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Unit_Cli_Data_LedgerTest
extends Horde_Kolab_Cli_TestCase
{
    public function testCountEmpty()
    {
        $ledger = new Horde_Kolab_Cli_Data_Ledger();
        $this->assertEquals(0, count($ledger));
    }

    public function testCount()
    {
        $this->assertEquals(2, count($this->_import()));
    }

    public function testAsXmlCount()
    {
        $this->assertEquals(2, count($this->_import()->asXml()));
    }

    public function testAsXml()
    {
        $entries = $this->_import()->asXml();
        foreach ($entries as $entry) {
            $this->assertContains('<entry xmlns:en="http://newartisans.com/xml/ledger-en" xmlns:tr="http://newartisans.com/xml/ledger-tr">', $entry);
        }
    }

    private function _import()
    {
        $ledger = new Horde_Kolab_Cli_Data_Ledger();
        $ledger->importFile(__DIR__ . '/../../../fixtures/ledger.xml');
        return $ledger;
    }
}
