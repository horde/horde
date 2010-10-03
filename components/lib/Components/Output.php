<?php
/**
 * Components_Output:: handles output from the components application.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Output:: handles output from the components application.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Output
{
    /**
     * The CLI handler.
     *
     * @var Horde_Cli
     */
    private $_cli;

    /**
     * Did the user request verbose output?
     *
     * @var boolean
     */
    private $_verbose;

    /**
     * Did the user request quiet output?
     *
     * @var boolean
     */
    private $_quiet;

    /**
     * Did the user request to avoid colored output?
     *
     * @var boolean
     */
    private $_nocolor;

    /**
     * Constructor.
     *
     * @param Horde_Cli         $cli    The CLI handler.
     * @param Components_Config $config The configuration for the current job.
     */
    public function __construct(
        Horde_Cli $cli,
        Components_Config $config
    ) {
        $this->_cli = $cli;
        $options = $config->getOptions();
        $this->_verbose = !empty($options['verbose']);
        $this->_quiet = !empty($options['quiet']);
        $this->_nocolor = !empty($options['nocolor']);
    }

    public function ok($text)
    {
        if ($this->_quiet) {
            return;
        }
        $this->_cli->message(
            $text,
            $this->_getType('cli.success')
        );
    }

    public function warn($text)
    {
        if ($this->_quiet) {
            return;
        }
        $this->_cli->message(
            $text,
            $this->_getType('cli.warning')
        );
    }

    public function fail($text)
    {
        $this->_cli->fatal($text);
    }

    public function pear($text)
    {
        if (!$this->_verbose) {
            return;
        }
        $this->_cli->message(
            '-------------------------------------------------',
            $this->_getType('cli.message')
        );
        $this->_cli->message(
            'PEAR output START',
            $this->_getType('cli.message')
        );
        $this->_cli->message(
            '-------------------------------------------------',
            $this->_getType('cli.message')
        );
        $this->_cli->writeln($text);
        $this->_cli->message(
            '-------------------------------------------------',
            $this->_getType('cli.message')
        );
        $this->_cli->message(
            'PEAR output END',
            $this->_getType('cli.message')
        );
        $this->_cli->message(
            '-------------------------------------------------',
            $this->_getType('cli.message')
        );
    }

    /**
     * Modify the type for the --nocolor switch.
     *
     * @param string $type The message to rewrite.
     *
     * @return string The message type that should be used for the output.
     */
    private function _getType($type)
    {
        if ($this->_nocolor) {
            return '';
        } else {
            return $type;
        }
    }
}