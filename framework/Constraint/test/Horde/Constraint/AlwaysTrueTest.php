<?php
class Horde_Constraint_AlwaysTrueTest extends Horde_Test_Case
{
    public static function randomObjectProvider()
    {
        return array(
            array('teststring'),
            array(''),
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider randomObjectProvider
     */
    public function testEvaluateIsAlwaysTrue($value)
    {
        $const = new Horde_Constraint_AlwaysTrue();
        $this->assertTrue($const->evaluate($value));
    }
}
