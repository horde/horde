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
{
    private $_opts;

    private $_args;

    /**
     * Constructor.
     *
     * @param Horde_Qc_Modules $modules A list of modules.
     */
    public function __construct(Horde_Qc_Modules $modules)
    {
        $parser = new Horde_Argv_Parser(
            array(
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );

        foreach ($modules as $module) {
            $parser->addOptionGroup($module->getOptions());
        }

        list($this->_opts, $this->_args) = $parser->parseArgs();
    }
}
