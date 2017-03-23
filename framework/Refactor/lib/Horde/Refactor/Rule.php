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
 * @property-read string[] $warnings Warning messages.
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
     * Rule configuration.
     *
     * @var Horde\Refactor\Config\Base
     */
    protected $_config;

    /**
     * Current list of tokens.
     *
     * @var Horde\Refactor\Tokens
     */
    protected $_tokens;

    /**
     * A list of error messages.
     *
     * @var string[]
     */
    protected $_errors = array();

    /**
     * A list of warning messages.
     *
     * @var string[]
     */
    protected $_warnings = array();

    /**
     * Constructor.
     *
     * @param string $file                        Name of the file to parse and
     *                                            refactor.
     * @param Horde\Refactor\Config\Base $config  The rule configuration.
     */
    public function __construct($file, Config\Base $config)
    {
        $this->_config = clone $config;
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

    /**
     * Getter.
     */
    public function __get($name)
    {
        if ($name == 'errors' || $name == 'warnings') {
            return $this->{'_' . $name};
        }
    }

    /**
     * Adds a warning.
     *
     * @param string $warning  A warning.
     */
    public function addWarning($warning)
    {
        if ($this->_pauseWarnings) {
            return;
        }
        $this->_warnings[] = $warning;
    }

    /**
     * Adds an error.
     *
     * @param string $error  A error.
     */
    public function addError($error)
    {
        if ($this->_pauseWarnings) {
            return;
        }
        $this->_errors[] = $error;
    }

    /**
     * Pauses the logging of warnings and errors.
     */
    public function pauseWarnings()
    {
        $this->_pauseWarnings = true;
    }

    /**
     * Resumes the logging of warnings and errors.
     */
    public function resumeWarnings()
    {
        $this->_pauseWarnings = false;
    }
}
