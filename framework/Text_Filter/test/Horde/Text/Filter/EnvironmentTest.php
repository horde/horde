<?php
/**
 * Horde_Text_Filter_Environment tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_EnvironmentTest extends PHPUnit_Framework_TestCase
{
    public function testEnvironment()
    {
        $tests = array(
            'Simple line' => 'Simple line',
            'Inline %FOO% variable' => 'Inline bar variable',
            '%FOO% at start' => 'bar at start',
            'at end %FOO%' => 'at end bar',
            '# %COMMENT% line' => '',
            'Variable %FOO% with # comment %COMMENT%' => 'Variable bar with ',
            'Simple line' => 'Simple line'
        );

        putenv('COMMENT=comment');
        putenv('FOO=bar');

        foreach ($tests as $key => $val) {
            $filter = Horde_Text_Filter::filter($key, 'environment');
            $this->assertEquals($val, $filter);
        }
    }

}
