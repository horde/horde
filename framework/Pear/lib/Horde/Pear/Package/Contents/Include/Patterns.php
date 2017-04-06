<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Pear
 */

/**
 * Horde_Pear_Package_Contents_Include implementation that includes files based
 * on a pattern list.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pear
 */
class Horde_Pear_Package_Contents_Include_Patterns
implements Horde_Pear_Package_Contents_Include
{
    /**
     * Helper object to match against patterns.
     *
     * @var Horde_Pear_Package_Contents_PatternsMatcher
     */
    protected $_matcher;

    /**
     * The root position of the repository.
     *
     * @var string
     */
    protected $_root;

    /**
     * Constructor.
     *
     * @param array $patterns The include patterns.
     * @param string $root    The root position for the files that should be
     *                        checked.
     */
    public function __construct($patterns, $root)
    {
        $this->_root = $root;
        $this->_matcher = new Horde_Pear_Package_Contents_PatternsMatcher(
            $patterns
        );
    }

    /**
     * Tell whether to include the element.
     *
     * @param SplFileInfo $element The element to check.
     *
     * @return bool True if the element should be included, false otherwise.
     */
    public function isIncluded(SplFileInfo $element)
    {
        return $this->_matcher->matches(
            substr($element->getPathname(), strlen($this->_root))
        );
    }
}