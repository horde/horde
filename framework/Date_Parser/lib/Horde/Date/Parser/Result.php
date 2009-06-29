<?php
class Horde_Date_Parser_Result
{
    public $span;
    public $tokens = array();

    public function __construct($span, $tokens)
    {
        $this->span = $span;
        $this->tokens = $tokens;
    }

    /**
     * Guess a specific time within the given span
     */
    public function guess()
    {
        if (! $this->span instanceof Horde_Date_Span) {
            return null;
        }

        if ($this->span->width() > 1) {
            return $this->span->begin->add($this->span->width() / 2);
        } else {
            return $this->span->begin;
        }
    }

    public function taggedText()
    {
        $taggedTokens = array_values(array_filter($this->tokens, create_function('$t', 'return $t->tagged();')));
        return implode(' ', array_map(create_function('$t', 'return $t->word;'), $taggedTokens));
    }

    public function untaggedText()
    {
        $untaggedTokens = array_values(array_filter($this->tokens, create_function('$t', 'return ! $t->tagged();')));
        return implode(' ', array_map(create_function('$t', 'return $t->word;'), $untaggedTokens));
    }

}
