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
        foreach ($this->pattern as $element) {
            $name = (string)$element;
            $optional = substr($name, -1) == '?';
            if ($optional) { $name = rtrim($name, '?'); }
            /*
        if element.instance_of? Symbol
          klass = constantize(name)
          match = tokens[token_index] && !tokens[token_index].tags.select { |o| o.kind_of?(klass) }.empty?
          return false if !match && !optional
          (token_index += 1; next) if match
          next if !match && optional
        elsif element.instance_of? String
            */
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
            /*
        else
          raise(ChronicPain, "Invalid match type: #{element.class}")
        end
            */
        }

        if ($tokenIndex != count($tokens)) { return false; }
        return true;
    }

}
