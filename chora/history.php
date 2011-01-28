<?php
/**
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('chora');
} catch (Exception $e) {
    Chora::fatal($e);
}

// TODO - This currently doesn't work.
Chora::fatal('History display is currently broken', '500 Internal Server Error');

/* Exit if it's not supported. */
if (!$VC->hasFeature('branches')) {
    Chora::url('browsefile', $where)->redirect();
}

$colset = array('#ccdeff', '#ecf', '#fec', '#efc', '#cfd', '#dcdba0');
$branch_colors = $colStack = array();
foreach ($branches as $brrev => $brcont) {
    if (!count($colStack)) {
        $colStack = $colset;
    }
    $branch_colors[$brrev] = array_shift($colset);
}

/**
 * This takes a row and a column, and recursively iterates through any
 * sub-revisions or branches from the value that was already in the
 * grid at the co-ordinates that it was called with.
 *
 * Calling this function on every revision of the trunk is enough to
 * render out the whole tree.
 */
function _populateGrid($row, $col)
{
    global $branches, $grid, $VC;

    /* Figure out the starting revision this function uses. */
    $rev = $grid[$row][$col];

    /* For every branch that is known, try to see if it forks here. */
    $brkeys = array_keys($branches);

    /* NOTE: do not optimise to use foreach () or each() here, as that
     * really screws up the $branches pointer array due to the
     * recursion, and parallel branches fail - avsm. */
    for ($a = 0, $aMax = count($brkeys); $a < $aMax; ++$a) {
        $brrev = $brkeys[$a];
        $brcont = $branches[$brrev];
        /* Check to see if current point matches a branch point. */
//        if (!strcmp($rev, $VC->strip($brrev, 1))) {
        if (!strcmp($rev, $brrev)) {
            /* If it does, figure out how many rows we have to add. */
            $numRows = sizeof($brcont);
            /* Check rows in columns to the right, until one is
             * free. */
            $insCol = $col + 1;
            while (true) {
                /* Look in the current column for a set value. */
                $inc = false;
                for ($i = $row; $i <= ($row + $numRows); ++$i) {
                    if (isset($grid[$i][$insCol])) {
                        $inc = true;
                    }
                }
                /* If a set value was found, shift to the right and
                 * try again.  Otherwise, break out of the loop. */
                if ($inc) {
                    if (!isset($grid[$row][$insCol])) {
                        $grid[$row][$insCol] = ':' . $brcont[0];
                    }
                    ++$insCol;
                } else {
                    break;
                }
            }

            /* Put a fork marker in the top of the branch. */
            $grid[$row][$insCol] = $brrev;

            /* Populate the grid with the branch values at this
             * point. */
            for ($i = 0; $i < $numRows; ++$i) {
                $grid[1 + $i + $row][$insCol] = $brcont[$i];
            }
            /* For each value just set, check for sub-branches, - but
             * in reverse (VERY IMPORTANT!). */
            for ($i = $numRows - 1; $i >= 0 ; --$i) {
                _populateGrid(1 + $i + $row, $insCol);
            }
        }
    }
}

/* Spawn the file object. */
try {
    $fl = $VC->getFileObject($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$revlist = $fl->getBranchList();

/* Start row at the bottom trunk revision.  Since branches always go
 * down, there can never be one above 1.1, and so this is a safe
 * location to start.  We will then work our way up, recursively
 * populating the grid with branch revisions. */
for ($row = sizeof($trunk) - 1; $row >= 0; $row--) {
    $grid[$row][0] = $trunk[$row];
    _populateGrid($row, 0);
}

/* Sort the grid array into row order, and determine the maximum
 * column size that we need to render out in HTML. */
ksort($grid);
$maxCol = 0;
foreach ($grid as $cols) {
    krsort($cols);
    list($val) = each($cols);
    $maxCol = max($val, $maxCol);
}

$title = sprintf(_("Source Branching View for %s"), $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)));
$extraLink = Chora::getFileViews($where, 'history');

require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/history/header.inc';

foreach ($grid as $row) {
    echo '<tr>';

    /* Start traversing the grid of rows and columns. */
    for ($i = 0; $i <= $maxCol; ++$i) {

        /* If this column has nothing in it, require a blank cell. */
        if (!isset($row[$i])) {
             $bg = '';
             require CHORA_TEMPLATES . '/history/blank.inc';
             continue;
        }

        /* Otherwise, this cell has content; determine what it is. */
        $rev = $row[$i];

//        if ($VC->isValidRevision($rev) && ($VC->sizeof($rev) % 2)) {
        if ($VC->isValidRevision($rev)) {
            /* This is a branch point, so put the info out. */
            $bg = isset($branch_colors[$rev]) ? $branch_colors[$rev] : '#e9e9e9';
            $symname = $fl->branches[$rev];
            require CHORA_TEMPLATES . '/history/branch_cell.inc';

        } elseif (preg_match('|^:|', $rev)) {
            /* This is a continuation cell, so render it with the
             * branch colour. */
//            $bgbr = $VC->strip(preg_replace('|^\:|', '', $rev), 1);
            $bg = isset($branch_colors[$bgbr]) ? $branch_colors[$bgbr] : '#e9e9e9';
            require CHORA_TEMPLATES . '/history/blank.inc';

        } elseif ($VC->isValidRevision($rev)) {
            /* This cell contains a revision, so render it. */
//            $bgbr = $VC->strip($rev, 1);
            $bg = isset($branch_colors[$bgbr]) ? $branch_colors[$bgbr] : '#e9e9e9';
            $log = $fl->logs[$rev];
            $author = Chora::showAuthorName($log->queryAuthor());
            $date = strftime('%e %b %Y', $log->queryDate());
            $lines = $log->queryChangedLines();
            require CHORA_TEMPLATES . '/history/rev.inc';

        } else {
            /* Exhausted other possibilities, just show a blank cell. */
            require CHORA_TEMPLATES . '/history/blank.inc';
        }
    }

    echo '</tr>';
}

require CHORA_TEMPLATES . '/history/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
