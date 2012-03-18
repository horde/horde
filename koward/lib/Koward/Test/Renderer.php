<?php

class Koward_Test_Renderer extends PHPUnit_Extensions_Story_ResultPrinter_HTML
{
    /**
     * Constructor.
     *
     * @param  mixed   $out
     * @throws InvalidArgumentException
     */
    public function __construct($out = NULL)
    {
        parent::__construct($out);

        $this->templatePath = sprintf(
          '%s%sTemplate%s',

          __DIR__,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param  string $buffer
     */
    public function write($buffer)
    {
        if ($this->out !== NULL) {
            fwrite($this->out, $buffer);

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        } else {

            print $buffer;

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        }
    }
}