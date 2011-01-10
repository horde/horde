<?php

class Horde_Cli_Modular_Stub_Module_One
implements Horde_Cli_Modular_Module
{
    public $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }


    public function getUsage()
    {
        return 'Use One';
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-s',
                '--something',
                array(
                    'action' => 'store',
                    'help'   => 'Base option'
                )
            ),
        );
    }

    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return true;
    }

    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Test Group Title';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'Test Group Description';
    }

    /**
     * Return the options for this module.
     *
     * @return array The group options.
     */
    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-g',
                '--group',
                array(
                    'action' => 'store',
                    'help'   => 'Group option'
                )
            ),
        );
    }
}