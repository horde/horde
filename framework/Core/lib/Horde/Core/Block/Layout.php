<?php
/**
 * Provides basic functionality for both managing and displaying blocks.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Block_Layout
{
    /**
     * Edit URL.
     *
     * @var string
     */
    protected $_editUrl;

    /**
     * View URL.
     *
     * @var string
     */
    protected $_viewUrl;

    /**
     * Returns whether the specified block may be removed.
     *
     * @param integer $row  A layout row.
     * @param integer $col  A layout column.
     *
     * @return boolean  True if this block may be removed.
     */
    public function isRemovable($row, $col)
    {
        global $conf;

        $app = $this->_layout[$row][$col]['app'];
        $type = $this->_layout[$row][$col]['params']['type'];
        $block = $app . ':' . $type;

        /* Check if the block is a fixed block. */
        if (!in_array($block, $conf['portal']['fixed_blocks'])) {
            return true;
        }

        /* Check if we have still another block of the same type. */
        $found = false;
        foreach ($this->_layout as $cur_row) {
            foreach ($cur_row as $cur_col) {
                if (isset($cur_col['app']) &&
                    $cur_col['app'] == $app &&
                    $cur_col['params']['type'] == $type) {
                    if ($found) {
                        return true;
                    }
                    $found = true;
                }
            }
        }

        return false;
    }

    /**
     * Returns an URL triggering an action to a block.
     *
     * @param string $action  An action to trigger.
     * @param integer $row    A layout row.
     * @param integer $col    A layout column.
     *
     * @return Horde_Url  An URL with all necessary parameters.
     */
    public function getActionUrl($action, $row, $col)
    {
        return Horde::url($this->_editUrl)->unique()->setAnchor('block')->add(array(
            'col' => $col,
            'row' => $row,
            'action' => $action,
            'url' => $this->_viewUrl
        ));
    }

    /**
     * Returns the actions for the block header.
     *
     * @param integer $row   A layout row.
     * @param integer $col   A layout column.
     * @param boolean $edit  Whether to include the edit icon.
     * @param $url TODO
     *
     * @return string  HTML code for the block action icons.
     */
    public function getHeaderIcons($row, $col, $edit, $url = null)
    {
        $icons = '';

        if ($edit) {
            $icons .= Horde::link($this->getActionUrl('edit', $row, $col),
                                  Horde_Core_Translation::t("Edit"))
                . Horde::img('edit.png', Horde_Core_Translation::t("Edit"))
                . '</a>';
        }

        if ($this->isRemovable($row, $col)) {
            $icons .= Horde::link(
                $this->getActionUrl('removeBlock', $row, $col), Horde_Core_Translation::t("Remove"),
                '', '',
                'return window.confirm(\''
                . addslashes(Horde_Core_Translation::t("Really delete this block?")) . '\')')
                . Horde::img('delete.png', Horde_Core_Translation::t("Remove"))
                . '</a>';
        }

        return $icons;
    }

}
