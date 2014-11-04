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
        require __DIR__ . '/fixtures/source/lib/Horde/Translation/Source/TestAutodetect.php';
        $this->assertEquals(
            'Heute',
            Horde_Translation_Source_TestAutodetect::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_Source_TestAutodetect::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_Source_TestAutodetect::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }

    public function testAutodetectPear()
    {
        require __DIR__ . '/fixtures/pear/php/Horde/Translation/Pear/TestAutodetect.php';
        Horde_Translation_Pear_TestAutodetect::init();
        $this->assertEquals(
            'Heute',
            Horde_Translation_Pear_TestAutodetect::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_Pear_TestAutodetect::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_Pear_TestAutodetect::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }

    public function testAutodetectComposer()
    {
        require __DIR__ . '/fixtures/composer/Horde/Translation/Composer/TestAutodetect.php';
        $this->assertEquals(
            'Heute',
            Horde_Translation_Composer_TestAutodetect::t('Today')
        );
        $this->assertEquals(
            'Today',
            Horde_Translation_Composer_TestAutodetect::r('Today')
        );
        $this->assertEquals(
            '1 Woche',
            sprintf(
                Horde_Translation_Composer_TestAutodetect::ngettext(
                    '%d week',
                    '%d weeks',
                    1),
                1
            )
        );
    }
}
