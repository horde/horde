<?php
/**
 * Horde_Element_Module_CiSetup:: generates the configuration for Hudson based
 * continuous integration of a Horde PEAR package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * Horde_Element_Module_CiSetup:: generates the configuration for Hudson based
 * continuous integration of a Horde PEAR package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
class Horde_Element_Module_CiSetup
implements Horde_Element_Module
{
    public function getOptionGroupTitle()
    {
        return 'Continuous Integration Setup';
    }

    public function getOptionGroupDescription()
    {
        return 'This module generates the configuration for Hudson based continuous integration of a Horde PEAR package.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-c',
                '--cisetup',
                array(
                    'action' => 'store',
                    'help'   => 'generate the basic Hudson project configuration for a Horde PEAR package in CISETUP'
                )
            ),
            new Horde_Argv_Option(
                '-C',
                '--ciprebuild',
                array(
                    'action' => 'store',
                    'help'   => 'generate the Hudson build configuration for a Horde PEAR package in CIPREBUILD'
                )
            ),
            new Horde_Argv_Option(
                '-T',
                '--toolsdir',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the PEAR installation holding the required analysis tools'
                )
            ),
        );
    }

    public function handle(Horde_Element_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['cisetup']) | !empty($options['ciprebuild'])) {
            $this->run($config);
        }
    }

    public function run(Horde_Element_Config $config)
    {
        $options = $config->getOptions();

        $pear = new PEAR();
        $pear->setErrorHandling(PEAR_ERROR_DIE);

        $arguments = $config->getArguments();
        $pkgfile = $arguments[0] . DIRECTORY_SEPARATOR . 'package.xml';
        $name = basename($arguments[0]);
        if (basename(dirname($arguments[0])) == 'framework') {
            $origin = 'framework' . DIRECTORY_SEPARATOR . $name;
        } else {
            $origin = $name;
        }
        $test_path = strtr($name, '_', '/');

        $pkg     = new PEAR_PackageFile(new PEAR_Config());
        $pf      = $pkg->fromPackageFile($pkgfile, PEAR_VALIDATE_NORMAL);
        $description = $pf->getDescription();

        if (!isset($options['toolsdir'])) {
            $options['toolsdir'] = 'php-hudson-tools/workspace/pear/pear';
        }

        if (!empty($options['cisetup'])) {
            $in = file_get_contents(
                Horde_Element_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-element-config.xml.template',
                'r'
            );
            file_put_contents(
                $options['cisetup'] . DIRECTORY_SEPARATOR . 'config.xml',
                sprintf($in, $origin, 'horde', $options['toolsdir'], $description)
            );
        }

        if (!empty($options['ciprebuild'])) {
            $in = file_get_contents(
                Horde_Element_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-element-build.xml.template',
                'r'
            );
            file_put_contents(
                $options['ciprebuild'] . DIRECTORY_SEPARATOR . 'build.xml',
                sprintf($in, $options['toolsdir'])
            );
            $in = file_get_contents(
                Horde_Element_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-element-phpunit.xml.template',
                'r'
            );
            file_put_contents(
                $options['ciprebuild'] . DIRECTORY_SEPARATOR . 'phpunit.xml',
                sprintf($in, $name, $test_path)
            );
        }
    }
}
