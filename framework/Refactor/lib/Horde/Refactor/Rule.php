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
 * This class represents both a file being refactored and a refactoring rule
 * applied to this file.
 *
 * Extend this class to implement actual refactorings.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
abstract class Rule
{
    /**
     * Current list of tokens.
     *
     * @var Horde\Refactor\Tokens
     */
    protected $_tokens;

    /**
     * Constructor.
     *
     * @param string $file  Name of the file to parse and refactor.
     */
    public function __construct($file)
    {
        $this->_tokens = new Tokens(
            token_get_all(file_get_contents($file))
        );
    }

    /**
     * Applies the actual refactoring to the tokenized code.
     */
    abstract public function run();

    /**
     * Returns the file code in its current state.
     *
     * @return string  The file code.
     */
    public function dump()
    {
        return (string)$this->_tokens;
    }
}
