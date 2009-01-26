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
    public function tag($new_tag)
    {
        $this->tags[] = $new_tag;
    }

    /**
     * Remove all tags of the given class
     */
    public function untag($tag_class)
    {
        $this->tags = array_filter($this->tags, create_function('$t', 'return $t instanceof ' . $tag_class . ';'));
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
    public function getTag($tag_class)
    {
        $matches = array_filter($this->tags, create_function('$t', 'return $t instanceof ' . $tag_class . ';'));
        return array_shift($matches);
    }

    /**
     * Print this Token in a pretty way
     */
    public function __toString()
    {
        return '(' . implode(', ', $this->tags) . ') ';
    }

}
