<?php
/**
 * The IMP_IMAP_Thread class provides functions to generate thread tree
 * images.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_IMAP_Thread
{
    /**
     * The thread data object.
     *
     * @var Horde_Imap_Client_Thread
     */
    protected $_thread;

    /**
     * Images used and their internal representations.
     *
     * @var array
     */
    static protected $_imglist = array(
        '0' => 'blank.png',
        '1' => 'line.png',
        '2' => 'join.png',
        '3' => 'joinbottom-down.png',
        '4' => 'joinbottom.png'
    );

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Thread $thread  The thread data object.
     */
    function __construct($thread)
    {
        $this->_thread = $thread;
    }

    /**
     * Generate the thread representation for the given index list in the
     * internal format (a string with each character representing the graphic
     * to be displayed from $_imglist).
     *
     * @param array $indices    The list of indices to create a tree for.
     * @param boolean $sortdir  True for newest first, false for oldest first.
     *
     * @return array  An array with the index as the key and the internal
     *                thread representation as the value.
     */
    public function getThreadTreeOb($indices, $sortdir)
    {
        $container = $last_level = $last_thread = null;
        $thread_level = $tree = array();
        $t = &$this->_thread;

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
                $line .= (!isset($thread_level[$i]) || ($thread_level[$i])) ? 0 : 1;
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
        $imgs = self::getImageUrls(false);
        foreach ($this->getThreadTreeOb($indices, $sortdir) as $k => $v) {
            $tree[$k] = '';
            for ($i = 0, $length = strlen($v); $i < $length; ++$i) {
                $tree[$k] .= $imgs[$v[$i]];
            }
        }
        return $tree;
    }

    /**
     * Get potential image URLs that may be used to display a thread.
     *
     * @param ids $ids      Add unique DOM ID to each image?
     * @param ids $datauri  Output data URIs, if possible?
     *
     * @return array  An array with the image code as a key and the image url
     *                as the value.
     */
    static public function getImageUrls($ids = true, $datauri = false)
    {
        $graphicsdir = $GLOBALS['registry']->getImageDir('horde');
        $args = array();

        foreach (self::$_imglist as $key => $val) {
            if ($ids) {
                $args['id'] = 'thread_img_' . $key;
            }
            if ($datauri) {
                $out[$key] = IMP::img('tree/' . (($key != 0 && !empty($GLOBALS['nls']['rtl'][$GLOBALS['language']])) ? ('rev-' . $val) : $val), '', $args, $graphicsdir);
            } else {
                $out[$key] = Horde::img('tree/' . (($key != 0 && !empty($GLOBALS['nls']['rtl'][$GLOBALS['language']])) ? ('rev-' . $val) : $val), '', $args, $graphicsdir);
            }
        }

        return $out;
    }
}
