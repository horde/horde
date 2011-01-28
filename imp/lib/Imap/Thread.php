<?php
/**
 * The IMP_Imap_Thread class provides functions to manipulate threaded sorts
 * of messages.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap_Thread
{
    /**
     * The thread data object.
     *
     * @var Horde_Imap_Client_Thread
     */
    protected $_thread;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Thread $thread  The thread data object.
     */
    public function __construct($thread)
    {
        $this->_thread = $thread;
    }

    /**
     * Generate the thread representation for the given index list in the
     * internal format.
     *
     * @param array $indices    The list of indices to create a tree for.
     * @param boolean $sortdir  True for newest first, false for oldest first.
     *
     * @return array  An array with the index as the key and the internal
     *                thread representation as the value.
     * <pre>
     * 0 - blank
     * 1 - line
     * 2 - join
     * 3 - joinbottom-down
     * 4 - joinbottom
     * </pre>
     */
    public function getThreadTreeOb($indices, $sortdir)
    {
        $container = $last_level = $last_thread = null;
        $thread_level = $tree = array();
        $t = &$this->_thread;

        if (empty($indices)) {
            return $tree;
        }

        $indices = array_intersect($t->messageList($sortdir), $indices);

        /* If starting in the middle of a thread, the threadLevel tree needs
         * to be built from the base of the current thread. */
        $first = reset($indices);
        foreach ($t->getThread(reset($indices)) as $val) {
            if ($first == $val) {
                break;
            }
            $thread_level[$t->getThreadIndent($val)] = $t->lastInLevel($val);
        }

        foreach ($indices as $val) {
            $tree[$val] = '';

            $indentBase = $t->getThreadBase($val);
            if (empty($indentBase)) {
                continue;
            }

            $lines = '';
            $indentLevel = $t->getThreadIndent($val);
            $lastinlevel = $t->lastInLevel($val);

            if ($lastinlevel && ($indentBase == $val)) {
                continue;
            }

            if ($lastinlevel) {
                $join_img = ($sortdir) ? 3 : 4;
            } elseif (($indentLevel == 1) && ($indentBase == $val)) {
                $join_img = ($sortdir) ? 4 : 3;
            } else {
                $join_img = 2;
            }

            $thread_level[$indentLevel] = $lastinlevel;
            $line = '';

            for ($i = 1; $i < $indentLevel; ++$i) {
                $line .= intval(isset($thread_level[$i]) && !$thread_level[$i]);
            }
            $tree[$val] = $line . $join_img;
        }

        return $tree;
    }

    /**
     * Generate the thread representation image for the given index list.
     *
     * @param array $indices    The list of indices to create a tree for.
     * @param boolean $sortdir  True for newest first, false for oldest first.
     *
     * @return array  An array with the index as the key and the thread image
     *                representation as the value.
     */
    public function getThreadImageTree($indices, $sortdir)
    {
        $tree = array();

        foreach ($this->getThreadTreeOb($indices, $sortdir) as $k => $v) {
            $tree[$k] = '';
            for ($i = 0, $length = strlen($v); $i < $length; ++$i) {
                $tree[$k] .= '<span class="treeImg treeImg' . $v[$i] . '"></span>';
            }
        }
        return $tree;
    }

    /**
     * Generate the thread representation text for the given index list.
     *
     * @param array $indices    The list of indices to create a tree for.
     * @param boolean $sortdir  True for newest first, false for oldest first.
     *
     * @return array  An array with the index as the key and the thread image
     *                representation as the value.
     */
    public function getThreadTextTree($indices, $sortdir)
    {
        $tree = array();

        foreach ($this->getThreadTreeOb($indices, $sortdir) as $k => $v) {
            $tmp = '';

            if (!empty($v)) {
                foreach (str_split($v) as $c) {
                    switch (intval($c)) {
                    case 0:
                        $tmp .= '  ';
                        break;

                    case '1':
                        $tmp .= '| ';
                        break;

                    case '2':
                        $tmp .= '|-';
                        break;

                    case '3':
                        $tmp .= '/-';
                        break;

                    case '4':
                        $tmp .= '\-';
                        break;
                    }
                }
            }

            $tree[$k] = $tmp;
        }

        return $tree;
    }

}
