<?php
class Horde_Constraint_OrTest extends Horde_Test_Case
{
    public function testOrEvaluatesTrueWhenOneConstraintIsTrue()
    {
        $c1 = new Horde_Constraint_AlwaysTrue();
        $c2 = new Horde_Constraint_AlwaysFalse();
        $or = new Horde_Constraint_Or($c1, $c2);

        $this->assertTrue($or->evaluate('test_string'));
    }

    public function testOrEvaluatesFalseWhenBothConstraintsAreFalse()
    {
        $c1 = new Horde_Constraint_AlwaysFalse();
        $c2 = new Horde_Constraint_AlwaysFalse();
        $or = new Horde_Constraint_Or($c1, $c2);

        $this->assertFalse($or->evaluate('test_string'));
    }

    public function testOrEvaluatesTrueWhenBothConstraintsAreTrue()
    {
        $c1 = new Horde_Constraint_AlwaysTrue();
        $c2 = new Horde_Constraint_AlwaysTrue();
        $or = new Horde_Constraint_Or($c1, $c2);

        $this->assertTrue($or->evaluate('test_string'));
    }

    public function testOrEvaluatesTrueWhenTrueConstraintIsAddedViaSetter()
    {
        $c1 = new Horde_Constraint_AlwaysFalse();
        $c2 = new Horde_Constraint_AlwaysFalse();
        $or = new Horde_Constraint_Or($c1, $c2);

        $or->addConstraint(new Horde_Constraint_AlwaysTrue());

        $this->assertTrue($or->evaluate('test_string'));
    }

    public function testOraddConstraintReturnsOrConstraint()
    {
        $c1 = new Horde_Constraint_AlwaysTrue();
        $c2 = new Horde_Constraint_AlwaysTrue();
        $or = new Horde_Constraint_Or($c1, $c2);

        $returnConst = $or->addConstraint(new Horde_Constraint_AlwaysFalse());

        $this->assertInstanceOf('Horde_Constraint_Or', $returnConst);
    }

    public function testReturnedOrEvaluatesTrueWhenTrueConstraintIsAddedViaSetter()
    {
        $c1 = new Horde_Constraint_AlwaysFalse();
        $c2 = new Horde_Constraint_AlwaysFalse();
        $or = new Horde_Constraint_Or($c1, $c2);

        $or = $or->addConstraint(new Horde_Constraint_AlwaysTrue());

        $this->assertTrue($or->evaluate('test_string'));
    }
}
