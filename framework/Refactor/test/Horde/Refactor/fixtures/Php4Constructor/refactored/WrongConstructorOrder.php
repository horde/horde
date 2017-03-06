<?php
class Foo
{
    /**
     * Constructor.
     */
    public function __construct(Bar $bar, $x = null)
    {
    }

    /**
     * Constructor.
     */
    public function Foo(Bar $bar, $x = null)
    {
        $this->__construct($bar, $x);
    }
}
