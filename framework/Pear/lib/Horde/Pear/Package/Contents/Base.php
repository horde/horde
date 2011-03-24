<?php
/**
 * The default content generator for package.xml files.
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
 * The default content generator for package.xml files.
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
class Horde_Pear_Package_Contents_Base
{
    /**
     * The file list handler
     *
     * @var Horde_Pear_Package_Contents_List
     */
    private $_list;

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Contents_List $list The file list handler.
     *
     * @return NULL
     */
    public function __construct(Horde_Pear_Package_Contents_List $list)
    {
        $this->_list = $list;
    }
}