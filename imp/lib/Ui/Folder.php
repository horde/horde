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
     * @param array $rows     Folder rows returned from IMP_Imap_Tree::build().
     * @param array $options  Additional options:
     * <pre>
     * 'expand_url' - (Horde_Url) The URL to use for expand/collapse links.
     * </pre>
     *
     * @return array  An array of tree image strings.
     */
    public function getTreeImages($rows, $options = array())
    {
        $this->_moreMbox = array();
        $out = array();

        reset($rows);
        while (list($key, $elt) = each($rows)) {
            $out[$key] = $this->_getTreeImage($elt, $options);
        }

        return $out;
    }

    /**
     * Create a tree image from a folder element entry.
     *
     * @param array $elt      An entry returned from IMP_Imap_Tree::element().
     * @param array $options  See self::getTreeImages().
     *
     * @return string  The image string.
     */
    protected function _getTreeImage($elt, $options = array())
    {
        global $registry;

        $alt = $dir = null;
        $dir2 = $elt['user_icon']
            ? Horde::img($elt['icon'], $elt['alt'], null, $elt['icondir'])
            : '<span class="foldersImg ' . $elt['class'] . '"></span>';

        if ($elt['children'] && isset($options['expand_url'])) {
            $dir = $options['expand_url']->copy()->add('folder', $elt['value']);

            if ($GLOBALS['injector']->getInstance('IMP_Imap_Tree')->isOpen($elt['base_elt'])) {
                if (!is_null($dir)) {
                    $dir->add('actionID', 'collapse_folder');
                    $alt = _("Collapse Folder");
                }

                if (empty($registry->nlsconfig['rtl'][$GLOBALS['language']])) {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 9
                        : ($elt['peek'] ? 10 : 11);
                } else {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 12
                        : ($elt['peek'] ? 13 : 14);
                }
            } else {
                if (!is_null($dir)) {
                    $dir->add('actionID', 'expand_folder');
                    $alt = _("Expand Folder");
                }

                if (empty($registry->nlsconfig['rtl'][$GLOBALS['language']])) {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 15
                        : ($elt['peek'] ? 16 : 17);
                } else {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 18
                        : ($elt['peek'] ? 19 : 20);
                }
            }

            if (!is_null($dir)) {
                $dir = Horde::link($dir, $alt) . '<span class="treeImg treeImg' . $tree_img . '"></span></a>' . $dir2;
            }
        } else {
            if (($elt['value'] == 'INBOX') && !$elt['peek']) {
                $dir = '<span class="treeImg"></span>' . $dir2;
            } else {
                if (empty($registry->nlsconfig['rtl'][$GLOBALS['language']])) {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 3
                        : ($elt['peek'] ? 2 : 4);
                } else {
                    $tree_img = ($elt['value'] == 'INBOX')
                        ? 7
                        : ($elt['peek'] ? 6 : 8);
                }
                $dir = '<span class="treeImg treeImg' . $tree_img . '"></span>' . $dir2;
            }
        }

        $line = '';
        $this->_moreMbox[$elt['level']] = $elt['peek'];
        for ($i = 0; $i < $elt['level']; $i++) {
            if ($this->_moreMbox[$i]) {
                $line .= '<span class="treeImg treeImg' . (empty($registry->nlsconfig['rtl'][$GLOBALS['language']]) ? 1 : 5) . '"></span>';
            } else {
                $line .= '<span class="treeImg"></span>';
            }
        }

        return $line . $dir;
    }

}
