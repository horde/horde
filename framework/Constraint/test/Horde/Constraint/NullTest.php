<?php
class Horde_Constraint_NullTest extends Horde_Test_Case
{
    public function testNullReturnsTrueWhenValueisNull()
    {
        $const = new Horde_Constraint_Null();
        $this->assertTrue($const->evaluate(null));
    }

    public function testNullReturnsFalseWhenValue_IsNot_Null()
    {
        $const = new Horde_Constraint_Null();
        $this->assertFalse($const->evaluate('not null value'));
    }
}
