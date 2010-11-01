<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */

class Horde_Util_VariablesTest extends PHPUnit_Framework_TestCase
{
    public function testRemove()
    {
        $vars = new Horde_Variables(array(
           'a' => 'a',
           'b' => 'b',
           'c' => array(1, 2, 3),
           'd' => array(
               'z' => 'z',
               'y' => array(
                   'f' => 'f',
                   'g' => 'g'
               )
           )
        ));

        $vars->remove('a');
        $vars->remove('d[y][g]');

        $this->assertNull($vars->a);
        $this->assertEquals('b', $vars->b);
        $this->assertEquals(array(1, 2, 3), $vars->c);
        $this->assertEquals(
            array('z' => 'z',
                  'y' => array('f' => 'f')),
            $vars->d
        );
    }
}
