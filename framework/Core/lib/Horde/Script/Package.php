<?php
/**
 * This class provides the information needed to output a javascript package
 * to the browser.
 *
 * A "package" contains all information to add to the page output to ensure
 * that the script can be used on the browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Script_Package implements IteratorAggregate
{
    /**
     * Javascript files to add to the page output.
     *
     * @var array
     */
    protected $_files = array();

    /* IteratorAggregate method. */

    public function getIterator()
    {
        return new ArrayIterator($this->_files);
    }

}
