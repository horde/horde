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
     * @var array
     */
    protected $_context = array();

    /**
     * @var array
     */
    protected $_leftLines = array();
    protected $_rightLines = array();
    protected $_leftNumbers = array();
    protected $_rightNumbers = array();

    /**
     * @var integer
     */
    protected $_leftLine = 0;
    protected $_rightLine = 0;

    public function diff(Horde_Vcs_File $file, $r1, $r2, $id = null)
    {
        try {
            $diff = $GLOBALS['VC']->diff($file, $r1, $r2, array('human' => true));
        } catch (Horde_Vcs_Exception $e) {
            return '<div class="diff"><p>' . sprintf(_("There was an error generating the diff: %s"), $e->getMessage()) . '</p></div>';
        }

        $this->_leftLines = array();
        $this->_rightLines = array();
        $this->_leftNumbers = array();
        $this->_rightNumbers = array();

        $firstSection = true;
        foreach ($diff as $section) {
            if (!$firstSection) {
                $this->_leftLines[] = array('type' => 'separator', 'lines' => array(''));
                $this->_rightLines[] = array('type' => 'separator', 'lines' => array(''));
                $this->_leftNumbers[] = '…';
                $this->_rightNumbers[] = '…';
            }
            $firstSection = false;

            $this->_leftLine = (int)$section['oldline'];
            $this->_rightLine = (int)$section['newline'];

            foreach ($section['contents'] as $change) {
                if ($this->hasContext() && $change['type'] != 'empty') {
                    $this->diffContext();
                }

                $method = 'diff' . ucfirst($change['type']);
                $this->$method($change);
            }

            if ($this->hasContext()) {
                $this->diffContext();
            }
        }

        return $this->render('app/views/diff/diff.html.php', array(
            'leftLines' => $this->_leftLines,
            'rightLines' => $this->_rightLines,
            'leftNumbers' => $this->_leftNumbers,
            'rightNumbers' => $this->_rightNumbers,
            'file' => $file,
            'r1' => $r1,
            'r2' => $r2,
            'id' => $id,
        ));
    }

    public function diffAdd($change)
    {
        $leftSection = array();
        $rightSection = array();
        foreach ($change['lines'] as $addedLine) {
            $leftSection[] = '';
            $rightSection[] = $addedLine;
            $this->_leftNumbers[] = '';
            $this->_rightNumbers[] = $this->_rightLine++;
        }
        $this->_leftLines[] = array('type' => 'added-empty', 'lines' => $leftSection);
        $this->_rightLines[] = array('type' => 'added', 'lines' => $rightSection);
        /*
        return $this->render('app/views/diff/added.html.php', array(
            'lines' => $change['lines'],
        ));
        */
    }

    public function diffRemove($change)
    {
        $leftSection = array();
        $rightSection = array();
        foreach ($change['lines'] as $removedLine) {
            $leftSection[] = $removedLine;
            $rightSection[] = '';
            $this->_leftNumbers[] = $this->_leftLine++;
            $this->_rightNumbers[] = '';
        }
        $this->_leftLines[] = array('type' => 'removed', 'lines' => $leftSection);
        $this->_rightLines[] = array('type' => 'removed-empty', 'lines' => $rightSection);
        /*
        return $this->render('app/views/diff/removed.html.php', array(
            'lines' => $change['lines'],
        ));
        */
    }

    public function diffEmpty($change)
    {
        $this->_context[] = array('left' => $this->_leftLine++, 'right' => $this->_rightLine++, 'text' => $change['line']);
        return '';
    }

    public function diffChange($change)
    {
        $leftSection = array();
        $rightSection = array();

        // Pop the old/new stacks one by one, until both are empty.
        $oldsize = count($change['old']);
        $newsize = count($change['new']);
        for ($row = 0, $rowMax = max($oldsize, $newsize); $row < $rowMax; ++$row) {
            if (isset($change['old'][$row])) {
                $leftSection[] = $change['old'][$row];
                $this->_leftNumbers[] = $this->_leftLine++;
            } else {
                $leftSection[] = '';
                $this->_leftNumbers[] = '';
            }

            if (isset($change['new'][$row])) {
                $rightSection[] = $change['new'][$row];
                $this->_rightNumbers[] = $this->_rightLine++;
            } else {
                $rightSection[] = '';
                $this->_rightNumbers[] = '';
            }
        }
        $this->_leftLines[] = array('type' => 'modified', 'lines' => $leftSection);
        $this->_rightLines[] = array('type' => 'modified', 'lines' => $rightSection);
        /*
        return $this->render('app/views/diff/change.html.php', array(
            'left' => $left,
            'right' => $right,
            'oldsize' => $oldsize,
            'newsize' => $newsize,
            'row' => $row,
        ));
        */
    }

    public function hasContext()
    {
        return !empty($this->_context);
    }

    public function diffContext()
    {
        $context = $this->_context;
        $this->_context = array();

        $leftSection = array();
        $rightSection = array();
        foreach ($context as $contextLine) {
            $leftSection[] = $contextLine['text'];
            $rightSection[] = $contextLine['text'];
            $this->_leftNumbers[] = $contextLine['left'];
            $this->_rightNumbers[] = $contextLine['right'];
        }
        $this->_leftLines[] = array('type' => 'unmodified', 'lines' => $leftSection);
        $this->_rightLines[] = array('type' => 'unmodified', 'lines' => $rightSection);
        /*
        return $this->render('app/views/diff/context.html.php', array(
            'context' => $context,
        ));
        */
    }

    public function diffCaption()
    {
        return $this->render('app/views/diff/caption.html.php');
    }
}
