<?php
class Components_Stub_Cli
extends Horde_Cli
{
    /**
     * Displays a fatal error message.
     *
     * @param mixed $error  The error text to display, an exception or an
     *                      object with a getMessage() method.
     */
    public function fatal($error)
    {
        if ($error instanceof Exception) {
            $trace = $error;
        } else {
            $trace = debug_backtrace();
        }
        $backtrace = new Horde_Support_Backtrace($trace);
        if (is_object($error) && method_exists($error, 'getMessage')) {
            $error = $error->getMessage();
        }
        $this->writeln($this->red('===================='));
        $this->writeln();
        $this->writeln($this->red(_("Fatal Error:")));
        $this->writeln($this->red($error));
        $this->writeln();
        $this->writeln((string)$backtrace);
        $this->writeln($this->red('===================='));
    }
}