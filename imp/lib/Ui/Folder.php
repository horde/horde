<?php
/**
 * The IMP_Ui_Folder:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for folders.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ui_Folder
{
    /**
     * Temporary array used to determine tree levels.
     *
     * @var array
     */
    protected $_moreMbox = array();

    /**
     * Create the tree images for a list of folder elements.
     *
     * @param IMP_Imap_Tree $tree  The tree object.
     * @param array $opts          Additional options:
     * <pre>
     * 'expand_url' - (Horde_Url) The URL to use for expand/collapse links.
     * </pre>
     *
     * @return array  An array of tree image strings.
     */
    public function getTreeImages(IMP_Imap_Tree $tree, $opts = array())
    {
        $this->_moreMbox = array();
        $out = array();

        foreach ($tree as $key => $val) {
            $out[$key] = $this->_getTreeImage($val, $opts);
        }

        return $out;
    }

    /**
     * Create a tree image from a folder element entry.
     *
     * @param IMP_Imap_Tree_Element $elt  A mailbox element object.
     * @param array $opts                 See self::getTreeImages().
     *
     * @return string  The image string.
     */
    protected function _getTreeImage($elt, $opts = array())
    {
        global $registry;

        $alt = $dir = null;
        $line = '';

        $icon = $elt->icon;
        $peek = false;

        $dir2 = $icon->user_icon
            ? Horde::img($icon->icon, $icon->alt)
            : '<span class="foldersImg ' . $icon->class . '"></span>';

        if ($elt->children && isset($opts['expand_url'])) {
            $dir = $opts['expand_url']->copy()->add('folder', $elt->value);

            if ($elt->is_open) {
                if (!is_null($dir)) {
                    $dir->add('actionID', 'collapse_folder');
                    $alt = _("Collapse Folder");
                }

                $tree_img = ($elt->value == 'INBOX')
                    ? 5
                    : ($peek ? 6 : 7);
            } else {
                if (!is_null($dir)) {
                    $dir->add('actionID', 'expand_folder');
                    $alt = _("Expand Folder");
                }

                $tree_img = ($elt->value == 'INBOX')
                        ? 9
                        : ($peek ? 10 : 11);
            }

            if (!is_null($dir)) {
                $dir = Horde::link($dir, $alt) . '<span class="treeImg treeImg' . $tree_img . '"></span></a>' . $dir2;
            }
        } else {
            if (($elt->value == 'INBOX') && !$peek) {
                $dir = '<span class="treeImg"></span>' . $dir2;
            } else {
                $tree_img = ($elt->value == 'INBOX')
                    ? 3
                    : ($peek ? 2 : 4);
                $dir = '<span class="treeImg treeImg' . $tree_img . '"></span>' . $dir2;
            }
        }

        $this->_moreMbox[$elt->level] = $peek;
        for ($i = 0; $i < $elt->level; ++$i) {
            $line .= $this->_moreMbox[$i]
                ? '<span class="treeImg treeImg1"></span>'
                : '<span class="treeImg"></span>';
        }

        return $line . $dir;
    }

}
