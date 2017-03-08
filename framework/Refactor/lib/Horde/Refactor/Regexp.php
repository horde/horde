<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Refactor
 */

namespace Horde\Refactor;

/**
 * Class for value objects representing a regular expression.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class Regexp
{
    /**
     * Regular expression string.
     *
     * @var string
     */
    protected $_regexp;

    /**
     * Constructor.
     *
     * @param string $regexp  A regular expression.
     */
    public function __construct($regexp)
    {
        $this->_regexp = $regexp;
    }

    /**
     * Returns the string representation.
     *
     * @return string  String representation of this regular expression.
     */
    public function __toString()
    {
        return $this->_regexp;
    }
}
