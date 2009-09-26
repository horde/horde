<?php
class Horde_Constraint_NotTest extends Horde_Test_Case
{
    public function testNotMakesFalseConstraintTrue()
    {
        $not = new Horde_Constraint_Not(new Horde_Constraint_AlwaysFalse());
        $this->assertTrue($not->evaluate('foo'));
    }

    public function testNotMakesTrueConstraintFalse()
    {
        $not = new Horde_Constraint_Not(new Horde_Constraint_AlwaysTrue());
        $this->assertFalse($not->evaluate('foo'));
    }
}
