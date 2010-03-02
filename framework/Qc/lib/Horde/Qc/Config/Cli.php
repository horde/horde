<?php
/**
 * Horde_Qc_Config_Cli:: class provides the command line interface for the Horde
 * quality control tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Horde_Qc_Config_Cli:: class provides the command line interface for the Horde
 * quality control tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
erver
 */
class Horde_Qc_Config_Cli
implements Horde_Qc_Config_Interface
{

    private $_parser;

    private $_opts;

    private $_args;

    /**
     * Constructor.
     *
     * @param Horde_Qc_Modules $modules A list of modules.
     */
    public function __construct(Horde_Qc_Modules $modules)
    {
        $options = array();

        foreach ($modules as $module) {
            $options = array_merge($options, $module->getOptions());
        }

        $this->_parser = new Horde_Argv_Parser(
            array(
                'optionList' => array_values($options),
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );
        list($this->_opts, $this->_args) = $parser->parseArgs();
    }
}
