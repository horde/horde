<?php
class Horde_Constraint_AndTest extends Horde_Test_Case
{
    public function testAndEvaluatesFalseWhenOneConstraintIsFalse()
    {
        $c1  = new Horde_Constraint_AlwaysTrue();
        $c2  = new Horde_Constraint_AlwaysFalse();
        $and = new Horde_Constraint_And($c1, $c2);

        $this->assertFalse($and->evaluate('test_string'));
    }

    public function testAndEvaluatesFalseWhenBothConstraintsAreFalse()
    {
        $c1  = new Horde_Constraint_AlwaysFalse();
        $c2  = new Horde_Constraint_AlwaysFalse();
        $and = new Horde_Constraint_And($c1, $c2);

        $this->assertFalse($and->evaluate('test_string'));
    }

    public function testAndEvaluatesTrueWhenBothConstraintsAreTrue()
    {
        $c1  = new Horde_Constraint_AlwaysTrue();
        $c2  = new Horde_Constraint_AlwaysTrue();
        $and = new Horde_Constraint_And($c1, $c2);

        $this->assertTrue($and->evaluate('test_string'));
    }

    public function testAndEvaluatesFalseWhenFalseConstraintIsAddedViaSetter()
    {
        $c1  = new Horde_Constraint_AlwaysTrue();
        $c2  = new Horde_Constraint_AlwaysTrue();
        $and = new Horde_Constraint_And($c1, $c2);

        $and->addConstraint(new Horde_Constraint_AlwaysFalse());

        $this->assertFalse($and->evaluate('test_string'));
    }

    public function testAndaddConstraintReturnsAndConstraint()
    {
        $c1  = new Horde_Constraint_AlwaysTrue();
        $c2  = new Horde_Constraint_AlwaysTrue();
        $and = new Horde_Constraint_And($c1, $c2);

        $returnConst = $and->addConstraint(new Horde_Constraint_AlwaysFalse());

        $this->assertInstanceOf('Horde_Constraint_And', $returnConst);
    }

    public function testReturnedAndEvaluatesFalseWhenFalseConstraintIsAddedViaSetter()
    {
        $c1  = new Horde_Constraint_AlwaysTrue();
        $c2  = new Horde_Constraint_AlwaysTrue();
        $and = new Horde_Constraint_And($c1, $c2);

        $and = $and->addConstraint(new Horde_Constraint_AlwaysFalse());

        $this->assertFalse($and->evaluate('test_string'));
    }
}
