<?php
class Foo
{
    /**
     * Constructor.
     */
    public function Foo(Bar $bar, $x = null)
    {
        $this->__construct($bar, $x);
    }

    /**
     * Constructor.
     */
    public function __construct(Bar $bar, $x = null)
    {
    }
}
