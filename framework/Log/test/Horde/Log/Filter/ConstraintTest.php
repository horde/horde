<?php
/**
 * Horde Log package
 *
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage UnitTests
 */

/**
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage UnitTests
 */
class Horde_Log_Filter_ConstraintTest extends Horde_Test_Case
{
    public function testFilterDoesNotAcceptWhenRequiredFieldIsMissing()
    {
        $event = array(
            'someotherfield' => 'other value',
        );
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRequiredField('required_field');

        $this->assertFalse($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRequiredFieldisPresent()
    {
        $event = array(
            'required_field' => 'somevalue',
            'someotherfield' => 'other value',
        );
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRequiredField('required_field');

        $this->assertTrue($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRegexMatchesField()
    {
        $event = array(
            'regex_field'    => 'somevalue',
            'someotherfield' => 'other value',
        );
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRegex('regex_field', '/somevalue/');

        $this->assertTrue($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRegex_DOESNOT_MatcheField()
    {
        $event = array(
            'regex_field'    => 'somevalue',
            'someotherfield' => 'other value',
        );
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRegex('regex_field', '/someothervalue/');

        $this->assertFalse($filterator->accept($event));
    }

    private function getConstraintMock($returnVal)
    {
        $const = $this->getMock('Horde_Constraint', array('evaluate'));
        $const->expects($this->once())
            ->method('evaluate')
            ->will($this->returnValue($returnVal));
        return $const;
    }

    public function testFilterCallsEvalOnAllConstraintsWhenTheyAreAllTrue()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));

        $filterator->accept(array('fieldname' => 'foo'));
    }

    public function testFilterStopsWhenItFindsAFalseCondition()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', new Horde_Constraint_AlwaysFalse());

        $const = $this->getMock('Horde_Constraint', array('evaluate'));
        $const->expects($this->never())
            ->method('evaluate');
        $filterator->addConstraint('fieldname', $const);
        $filterator->accept(array('fieldname' => 'foo'));

    }

    public function testFilterAcceptCallsConstraintOnNullWhenFieldDoesnotExist()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $const = $this->getMock('Horde_Constraint', array('evaluate'));
        $const->expects($this->once())
            ->method('evaluate')
            ->with(null);
        $filterator->addConstraint('fieldname', $const);
        $filterator->accept(array('someotherfield' => 'foo'));
    }
}
