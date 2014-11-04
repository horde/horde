<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_AutodetectTest extends Horde_Translation_TestBase
{
    public function testAutodetectSource()
    {
        require __DIR__ . '/fixtures/source/lib/Horde/Translation/TestAutodetectSource.php';
        $this->assertEquals(
            'Heute',
            Horde_Translation_TestAutodetectSource::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_TestAutodetectSource::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_TestAutodetectSource::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }

    public function testAutodetectPear()
    {
        require __DIR__ . '/fixtures/pear/php/Horde/Translation/TestAutodetectPear.php';
        $this->assertEquals(
            'Heute',
            Horde_Translation_TestAutodetectPear::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_TestAutodetectPear::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_TestAutodetectPear::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }

    public function testAutodetectComposer()
    {
        require __DIR__ . '/fixtures/composer/Horde/Translation/TestAutodetectComposer.php';
        $this->assertEquals(
            'Heute',
            Horde_Translation_TestAutodetectComposer::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_TestAutodetectComposer::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_TestAutodetectComposer::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }
}
