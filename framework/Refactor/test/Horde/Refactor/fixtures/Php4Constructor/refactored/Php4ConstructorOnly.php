<?php
class Foo extends Bar
{
    /**
     * Constructor.
     */
    public function __construct(Bar $bar, $x = null)
    {
        parent::__construct($bar);
    }

    /**
     * Constructor.
     */
    public function Foo(Bar $bar, $x = null)
    {
        $this->__construct($bar, $x);
    }
}
