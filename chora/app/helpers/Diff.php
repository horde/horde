<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */
class Chora_Diff_Helper extends Horde_View_Helper_Base
{
    /**
     * @var string
     */
    protected $_context = '';

    public function diff($diff)
    {
        if (!$diff) {
            return '<p>' . _("No Visible Changes") . '</p>';
        }

        return $this->render('app/views/diff/diff.html.php', array('diff' => $diff));
    }

    public function diffAdd($change)
    {
        return $this->render('app/views/diff/added.html.php', array(
            'lines' => $change['lines'],
        ));
    }

    public function diffRemove($change)
    {
        return $this->render('app/views/diff/removed.html.php', array(
            'lines' => $change['lines'],
        ));
    }

    public function diffEmpty($change)
    {
        $this->_context .= $this->escape($change['line']) . '<br>';
        return '';
    }

    public function diffChange($change)
    {
        // Pop the old/new stacks one by one, until both are empty.
        $oldsize = count($change['old']);
        $newsize = count($change['new']);
        $left = $right = '';
        for ($row = 0, $rowMax = max($oldsize, $newsize); $row < $rowMax; ++$row) {
            $left .= (isset($change['old'][$row]) ? $this->escape($change['old'][$row]) : '') . '<br>';
            $right .= (isset($change['new'][$row]) ? $this->escape($change['new'][$row]) : '') . '<br>';
        }

        return $this->render('app/views/diff/change.html.php', array(
            'left' => $left,
            'right' => $right,
            'oldsize' => $oldsize,
            'newsize' => $newsize,
            'row' => $row,
        ));
    }

    public function hasContext()
    {
        return !empty($this->_context);
    }

    public function diffContext()
    {
        $context = $this->_context;
        $this->_context = '';

        return $this->render('app/views/diff/context.html.php', array(
            'context' => $context,
        ));
    }

    public function diffCaption()
    {
        return $this->render('app/views/diff/caption.html.php');
    }
}
