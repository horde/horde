<?php
/**
 * Horde_Yaml test helpers
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_Test_Serializable implements Serializable
{
    private $string = null;

    public function __construct($string = null)
    {
        if (null === $string)
            throw new Exception('This is not supposed to be called implicitly');

        $this->string = $string;
    }

    public function serialize()
    {
        return $this->string;
    }

    public function unserialize($serialized)
    {
        $this->string = $serialized;
    }

    public function test()
    {
        return $this->string;
    }

}

class Horde_Yaml_Test_NotSerializable
{}
