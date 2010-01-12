<?php
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
