<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date_Parser
 */

/**
 *
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date_Parser
 */
class Horde_Date_Parser_Handler
{
    public $pattern;
    public $handlerMethod;

    public function __construct($pattern, $handlerMethod)
    {
        $this->pattern = $pattern;
        $this->handlerMethod = $handlerMethod;
    }

    public function match($tokens, $definitions)
    {
        $tokenIndex = 0;
        foreach ($this->pattern as $name) {
            $optional = substr($name, -1) == '?';
            if ($optional) { $name = rtrim($name, '?'); }

            $tag = substr($name, 0, 1) == ':';
            if ($tag) {
                $name = substr($name, 1);
                //match = tokens[token_index] && !tokens[token_index].tags.select { |o| o.kind_of?(klass) }.empty?
                $match = isset($tokens[$tokenIndex]) && $tokens[$tokenIndex]->getTag($name);
                if (!$match && !$optional) { return false; }
                if ($match) { $tokenIndex++; continue; }
                if (!$match && $optional) { continue; }
            } else {
                if ($optional && $tokenIndex == count($tokens)) { return true; }
                if (!isset($definitions[$name])) {
                    throw new Horde_Date_Parser_Exception("Invalid subset $name specified");
                }
                $subHandlers = $definitions[$name];
                foreach ($subHandlers as $subHandler) {
                    if ($subHandler->match(array_slice($tokens, $tokenIndex), $definitions)) {
                        return true;
                    }
                }
                return false;
            }
        }

        if ($tokenIndex != count($tokens)) { return false; }
        return true;
    }

}
