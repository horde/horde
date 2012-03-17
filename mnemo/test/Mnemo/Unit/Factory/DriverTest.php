<?php
/**
 * Test the Mnemo library.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the Mnemo library.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */
class Mnemo_Unit_Factory_DriverTest extends Mnemo_TestCase
{
    public function testCreateSql()
    {
        $injector = $this->getInjector();
        $injector->setInstance('Horde_Db_Adapter', 'DUMMY');
        $factory = $injector->getInstance('Mnemo_Factory_Driver');
        $GLOBALS['conf']['umask'] = '';
        $GLOBALS['conf']['storage']['driver'] = 'sql';
        $GLOBALS['conf']['storage']['params']['charset'] = 'utf-8';
        $this->assertInstanceOf('Mnemo_Driver_Sql', $factory->create('test'));
    }

    public function testCreateKolab()
    {
        self::createKolabSetup();
        list($share, $other_share) = self::_createDefaultShares();
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $this->assertInstanceOf(
            'Mnemo_Driver_Kolab',
            $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create(
                $share->getName()
            )
        );
    }

    public function testCreateKolabEmpty()
    {
        self::createKolabSetup();
        list($share, $other_share) = self::_createDefaultShares();
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $this->assertInstanceOf(
            'Mnemo_Driver_Kolab',
            $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create('')
        );
    }
}
