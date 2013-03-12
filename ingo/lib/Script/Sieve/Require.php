<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Sieve_Require class represents a Sieve Require statement.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Sieve_Require implements Ingo_Script_Item
{
    /**
     * @var array
     */
    protected $_requires;

    /**
     * Constructor.
     *
     * @param array $requires  The required extensions.
     */
    public function __construct(array $requires)
    {
        $this->_requires = $requires;
    }

    /**
     * Returns a script snippet representing this rule.
     *
     * @return string  A Sieve script snippet.
     */
    public function generate()
    {
        if (count($this->_requires) > 1) {
            $stringlist = '';
            foreach ($this->_requires as $require) {
                $stringlist .= (empty($stringlist)) ? '"' : ', "';
                $stringlist .= $require . '"';
            }
            return 'require [' . $stringlist . '];';
        }
        if (count($this->_requires) == 1) {
            return 'require "' . $this->_requires[0] . '";';
        }
        return '';
    }
}
