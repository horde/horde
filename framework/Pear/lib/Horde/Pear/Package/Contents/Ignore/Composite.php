<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Pear
 */

/**
 * Horde_Pear_Package_Contents_Ignore_Composite:: combines several ignore
 * handlers.
 *
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pear
 */
class Horde_Pear_Package_Contents_Ignore_Composite
implements Horde_Pear_Package_Contents_Ignore
{
    /**
     * The ignore handlers.
     *
     * @var array
     */
    private $_ignores;

    /**
     * Constructor.
     *
     * @param array $ignores The ignore handlers.
     */
    public function __construct($ignores)
    {
        $this->_ignores = $ignores;
    }

    /**
     * Tell whether to ignore the element.
     *
     * @param SplFileInfo $element The element to check.
     *
     * @return bool True if the element should be ignored, false otherwise.
     */
    public function isIgnored(SplFileInfo $element)
    {
        foreach ($this->_ignores as $ignore) {
            if ($ignore->isIgnored($element)) {
                return true;
            }
        }
        return false;
    }
}