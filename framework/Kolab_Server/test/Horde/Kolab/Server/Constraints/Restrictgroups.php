<?php

class Horde_Kolab_Server_Constraint_Restrictgroups extends PHPUnit_Framework_Constraint
{
    public function evaluate($other, $description = '', $returnResult = FALSE)
    {
        if ($other instanceOf Horde_Kolab_Server_Query_Element_Interface) {
            if ($other instanceOf Horde_Kolab_Server_Query_Element_Group) {
                $elements = $other->getElements();
                foreach ($elements as $element) {
                    if ($this->evaluate($element)) {
                        return true;
                    }
                }
                return true;
            } else {
                if ($other->getName() == 'objectClass'
                    && $other->getValue() == Horde_Kolab_Server_Object_Groupofnames::OBJECTCLASS_GROUPOFNAMES) {
                    return true;
                } else {
                    return false;
                }                    
            }
        } else {
            return false;
        }
    }

    public function fail($other, $description, PHPUnit_Framework_ComparisonFailure $comparisonFailure = NULL)
    {
        throw new PHPUnit_Framework_ExpectationFailedException(
          sprintf(
            '%sFailed asserting that %s contains a query element that restricts the search to groups',

            !empty($description) ? $description . "\n" : '',
            PHPUnit_Util_Type::toString($other, TRUE)
          ),
          NULL
        );
    }

    public function toString()
    {
        return 'contains a query element that restricts the search to groups';
    }
}
