<?php
/**
 * Test the Kolab configuration handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Kolab configuration handler.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */
class Horde_Kolab_Config_Integration_ConfigTest
extends Horde_Kolab_Config_ConfigStoryTestCase
{
    /**
     * @scenario
     */
    public function aMissingGlobalConfigurationFileThrowsAnException()
    {
        $this->given('that no Kolab server configuration file can be found')
            ->when('reading the configuration')
            ->then('the Config Object will throw an exception of type', 'Horde_Kolab_Config_Exception')
            ->and('the exception has the message',
                  'No configuration files found in '
                  . realpath(dirname(__FILE__) . '/../fixture/empty') . '.'
            );
    }

    /**
     * @scenario
     */
    public function theNameOfTheGlobalConfigurationDirectoryAndFileCanBeSpecified()
    {
        $this->given('that a global configuration file was specified as a combination of a directory path and a file name')
            ->when('reading the parameter', 'global')
            ->then('the result will be', 'global');
    }

    /**
     * @scenario
     */
    public function theNameOfTheConfigurationDirectoryCanBeSpecified()
    {
        $this->given('that the location of the configuration files were specified with a directory path')
            ->when('reading the parameter', 'local')
            ->then('the result will be', 'local');
    }

    /**
     * @scenario
     */
    public function readingAConfigurationYieldsTheGlobalConfigurationValueIfTheLocalOneIsMissing()
    {
        $this->given('that the location of the configuration files were specified with a directory path')
            ->when('reading the parameter', 'only_global')
            ->then('the result will be', 'global');
    }

    /**
     * @scenario
     */
    public function readingAConfigurationYieldsTheLocalConfigurationValueIfItIsSetInBothConfigurationFiles()
    {
        $this->given('that the location of the configuration files were specified with a directory path')
            ->when('reading the parameter', 'both')
            ->then('the result will be', 'local');
    }

    /**
     * @scenario
     */
    public function readingAConfigurationWithAnInvalidKeyThrowsAnException()
    {
        $this->given('that the location of the configuration files were specified with a directory path')
            ->when('reading the parameter', array())
            ->then('the Config Object will throw an exception of type', 'InvalidArgumentException')
            ->and(
                'the exception has the message',
                'The key must be a non-empty string!'
            );
    }

    /**
     * @scenario
     */
    public function tryingToReadAMissingConfigurationValueThrowsAnException()
    {
        $this->given('that the location of the configuration files were specified with a directory path')
            ->when('reading the parameter', 'unknown')
            ->then('the Config Object will throw an exception of type', 'Horde_Kolab_Config_Exception')
            ->and(
                'the exception has the message',
                'Parameter "unknown" has no value!'
            );
    }
}