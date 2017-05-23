<?php
/**
 * Example for an encapsulated CLI application.
 */

use Horde\Cli\Application;

/**
 * Application class.
 */
class MyApplication extends Application
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            null,
            array(
                'epilog' => 'Please report any bugs to https://bugs.horde.org.',
                'description' => 'This is a Horde CLI application',
                'version' => '%prog 1.0.0',
            )
        );
        $this->addOption(
            '-f', '--foo',
            array(
                'action' => 'store_const',
                'const' => 42,
                'dest' => 'bar',
                'help' => 'Enable bar.',
            )
        );
        $this->addOption(
            '-i', '--int',
            array(
                'action' => 'store',
                'type' => 'int',
                'help' => 'An integer.',
            )
        );
    }

    /**
     * Excecutes the actual application logic.
     */
    protected function _doRun()
    {
        if ($this->values->bar) {
            $this->message('The answer is ' . $this->values->bar, 'cli.success');
        } else {
            $this->message('You didn\'t pass --foo', 'cli.warning');
        }
    }
}
