<?php
/**
 * This class provides the application configuration for the test script.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */
class Pastie_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array();

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array();

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array();

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array();

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array();

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }
}
