<?php
/**
 * The default factory for elements provided by this package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * The default factory for elements provided by this package.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Factory
{
    /**
     * Generate an instance of the package contents handler.
     *
     * @param string $root The package root.
     *
     * @return Horde_Pear_Package_Contents The content handler.
     */
    public function createContents($root)
    {
        return new Horde_Pear_Package_Contents_Base(
            new Horde_Pear_Package_Contents_List(
                $root,
                new Horde_Pear_Package_Contents_Include_All(),
                new Horde_Pear_Package_Contents_Ignore_Composite(
                    array(
                        new Horde_Pear_Package_Contents_Ignore_Dot(),
                    )
                )
            )
        );
    }
}