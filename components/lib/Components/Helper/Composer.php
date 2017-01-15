<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */
class Components_Helper_Composer
{
    /**
     * Updates the composer.json file.
     *
     * @param string $package  Path to the package.xml file.
     * @param array  $options  The set of options for the operation.
     */
    public function generateComposeJson($package, array $options = array())
    {
        require_once __DIR__ . '/../../Conductor/PEARPackageFilev2.php';
        require_once __DIR__ . '/../../Conductor/Package2XmlToComposer.php';

        $converter = new Package2XmlToComposer($package);
        $converter->setRepositories(array(
            array('pear', 'http://pear.horde.org')
        ));
        $converter->output_file = dirname($package) . '/composer.json';
        $converter->convert();

        if (isset($options['logger'])) {
            $options['logger']->ok(
                'Created composer.json file.'
            );
        }
    }

}
