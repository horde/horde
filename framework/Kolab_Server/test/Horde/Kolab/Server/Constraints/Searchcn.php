<?php

class Horde_Kolab_Server_Constraint_Searchcn extends PHPUnit_Framework_Constraint
{
    public function evaluate($other)
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
                if ($other->getName() == 'cn') {
                    return true;
                } else {
                    return false;
                }                    
            }
        } else {
            return false;
        }
    }

    public function fail($other, $description, $not = FALSE)
    {
        throw new PHPUnit_Framework_ExpectationFailedException(
          sprintf(
            '%sFailed asserting that %s contains a query element that is searching by cn',

            !empty($description) ? $description . "\n" : '',
            PHPUnit_Util_Type::toString($other, TRUE)
          ),
          NULL
        );
    }

    public function toString()
    {
        return 'contains a query element that is searching by cn';
    }
}
