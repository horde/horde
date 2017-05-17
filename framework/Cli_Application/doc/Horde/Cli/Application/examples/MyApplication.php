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
                'optionList' => array(
                    new Horde_Argv_Option(
                        '-f', '--foo',
                        array(
                            'action' => 'store_const',
                            'const' => 42,
                            'dest' => 'bar',
                            'help' => 'Enable bar.',
                        )
                    ),
                    new Horde_Argv_Option(
                        '-i', '--int',
                        array(
                            'action' => 'store',
                            'type' => 'int',
                            'help' => 'An integer.',
                        )
                    )
                )
            )
        );
    }
}
