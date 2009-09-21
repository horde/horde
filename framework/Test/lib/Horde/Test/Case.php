<?php
class Horde_Test_Case extends PHPUnit_Framework_TestCase
{
    /**
     * Useful shorthand if you are mocking a class with a private constructor
     */
    public function getMockSkipConstructor($className, array $methods = array(), array $arguments = array(), $mockClassName = '')
    {
        return $this->getMock($className, $methods, $arguments, $mockClassName, /* $callOriginalConstructor */ false);
    }
}
