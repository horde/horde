<?php
/**
 * Components_Module_DevPackage:: generates a development snapshot for the
 * specified package.
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
 * Components_Module_DevPackage:: generates a development snapshot for the
 * specified package.
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
class Components_Module_DevPackage
implements Components_Module
{
    public function getOptionGroupTitle()
    {
        return 'Development Packages';
    }

    public function getOptionGroupDescription()
    {
        return 'This module generates a development snapshot for the specified package';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-d',
                '--devpackage',
                array(
                    'action' => 'store_true',
                    'help'   => 'generate a development snapshot'
                )
            ),
        );
    }

    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['devpackage'])) {
            $this->run($config);
        }
    }

    public function run(Components_Config $config)
    {
        $options = $config->getOptions();

        $pear = new PEAR();
        $pear->setErrorHandling(PEAR_ERROR_DIE);

        $arguments = $config->getArguments();
        $pkgfile = $arguments[0] . DIRECTORY_SEPARATOR . 'package.xml';

        $pkg     = new PEAR_PackageFile(new PEAR_Config());
        $pf      = $pkg->fromPackageFile($pkgfile, PEAR_VALIDATE_NORMAL);
        $pf->_packageInfo['version']['release'] = $pf->getVersion()
            . 'dev' . strftime('%Y%m%d%H%M');
        $gen     = $pf->getDefaultGenerator();
        $tgzfile = $gen->toTgz(new PEAR_Common());
    }
}
