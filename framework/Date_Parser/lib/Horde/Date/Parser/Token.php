<?php
class Horde_Date_Parser_Token
{
    public $word;
    public $tags;

    public function __construct($word)
    {
        $this->word = $word;
        $this->tags = array();
    }

    /**
     * Tag this token with the specified tag
     */
    public function tag($tagClass, $tag)
    {
        $this->tags[] = array($tagClass, $tag);
    }

    /**
     * Remove all tags of the given class
     */
    public function untag($tagClass)
    {
        $this->tags = array_filter($this->tags, create_function('$t', 'return substr($t[0], 0, ' . strlen($tagClass) . ') != "' . $tagClass . '";'));
    }

    /**
     * Return true if this token has any tags
     */
    public function tagged()
    {
        return count($this->tags) > 0;
    }

    /**
     * Return the Tag that matches the given class
     */
    public function getTag($tagClass)
    {
        $matches = array_filter($this->tags, create_function('$t', 'return substr($t[0], 0, ' . strlen($tagClass) . ') == "' . $tagClass . '";'));
        $match = array_shift($matches);
        return $match[1];
    }

    /**
     * Print this Token in a pretty way
     */
    public function __toString()
    {
        return '(' . implode(', ', $this->tags) . ') ';
    }

}
