<?php
class Nag_QuickParser
{
    protected $_indentStack;

    public function __construct($stack = null)
    {
        if ($stack === null) { $stack = new Horde_Support_Stack(); }
        $this->_indentStack = $stack;
    }

    public function parse($text)
    {
        $text = str_replace("\t", '    ', $text);
        $lines = preg_split('/[\r\n]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $parents = array();
        $tasks = array();
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match('/^\s*$/', $line)) { continue; }

            $indented = preg_match('/^([*-\s]+)(.*)$/', $line, $matches);
            if (!$indented) {
                $tasks[] = $line;
                $parents[$this->_indentStack->peek()] = count($tasks) - 1;
            } else {
                $line = $matches[2];
                $indent = strlen($matches[1]);
                if ($indent == $this->_indentStack->peek()) {
                    $parent = $parents[$this->_indentStack->peek(2)];
                    $tasks[] = array($line, 'parent' => $parent);
                } elseif ($indent > $this->_indentStack->peek()) {
                    $parent = $parents[$this->_indentStack->peek()];
                    $this->_indentStack->push($indent);
                    $tasks[] = array($line, 'parent' => $parent);
                    $parents[$this->_indentStack->peek()] = count($tasks) - 1;
                } else {
                    while ($this->_indentStack->pop() > $indent);

                    $parents[$indent] = $parents[$this->_indentStack->peek()];
                    $this->_indentStack->pop(); $this->_indentStack->push($indent);
                    $parent = $parents[$this->_indentStack->peek()];
                    if ($parent !== null) {
                        $tasks[] = array($line, 'parent' => $parent);
                    } else {
                        $tasks[] = $line;
                    }
                    $parents[$this->_indentStack->peek()] = count($tasks) - 1;
                }
            }
        }

        return $tasks;
    }
}
