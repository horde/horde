<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category Horde
 * @package  Wicked
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

require_once 'Text/Wiki/Parse/Default/Table.php';

/**
 * Parser class for table elements.
 *
 * Keeps track of columns width, necessary for reST rendering.
 *
 * @category Horde
 * @package  Wicked
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, versi */
class Text_Wiki_Parse_Table2 extends Text_Wiki_Parse_Table
{
    /**
     * Generates a replacement for the matched text.
     *
     * Token options are:
     *
     * 'type' =>
     *     'table_start' : the start of a bullet list
     *     'table_end'   : the end of a bullet list
     *     'row_start' : the start of a number list
     *     'row_end'   : the end of a number list
     *     'cell_start'   : the start of item text (bullet or number)
     *     'cell_end'     : the end of item text (bullet or number)
     *
     * 'cols' => the number of columns in the table (for 'table_start')
     *
     * 'rows' => the number of rows in the table (for 'table_start')
     *
     * 'span' => column span (for 'cell_start')
     *
     * 'attr' => column attribute flag (for 'cell_start')
     *
     * @param array $matches  The array of matches from parse().
     *
     * @return string  A series of text and delimited tokens marking the
     *                 different table elements and cell text.
     *
     */
    public function process($matches)
    {
        // Build the structure first and hope that the table isn't too large.
        $table = array();

        // the  width of columns in the table
        $widths = array();

        // rows are separated by newlines in the matched text
        $rows = explode("\n", $matches[1]);

        // loop through each row
        foreach ($rows as $row) {
            $table_row = array();

            // cells are separated by double-pipes
            $cells = explode('||', $row);

            // get the number of cells (columns) in this row
            $last = count($cells) - 1;

            // by default, cells span only one column (their own)
            $span = 1;

            // ignore cell zero, and ignore the "last" cell; cell zero
            // is before the first double-pipe, and the "last" cell is
            // after the last double-pipe. both are always empty.
            for ($i = 1; $i < $last; $i++) {
                // if there is no content at all, then it's an instance
                // of two sets of || next to each other, indicating a
                // span.
                if ($cells[$i] == '') {
                    // add to the span and loop to the next cell
                    $span += 1;
                    continue;
                }

                // this cell has content.
                $table_cell = array(
                    'attr' => null,
                    'span' => $span,
                );

                // find any special "attr"ibute cell markers
                switch (substr($cells[$i], 0, 2)) {
                case '> ':
                    // right-align
                    $table_cell['attr'] = 'right';
                    $content = substr($cells[$i], 2);
                    break;
                case '= ':
                    // center-align
                    $table_cell['attr'] = 'center';
                    $content = substr($cells[$i], 2);
                    break;
                case '< ':
                    // left-align
                    $table_cell['attr'] = 'left';
                    $content = substr($cells[$i], 2);
                    break;
                case '~ ':
                    $table_cell['attr'] = 'header';
                    $content = substr($cells[$i], 2);
                    break;
                default:
                    $content = $cells[$i];
                }

                $table_cell['content'] = trim($content);
                $table_cell['length'] = Horde_String::length(
                    $table_cell['content']
                );
                $table_row[] = $table_cell;

                // Record the column widths.
                if (isset($widths[$i])) {
                    $length = $table_cell['length'];
                    for ($j = $table_cell['span']; $j > 1; $j--) {
                        if (isset($widths[$i - $j + 1])) {
                            $length -= $widths[$i - $j + 1];
                        }
                    }
                    $widths[$i] = max($widths[$i], $length);
                } else {
                    $widths[$i] = ceil($table_cell['length'] / $table_cell['span']);
                }

                // reset the span.
                $span = 1;
            }

            $table[] = $table_row;
        }

        // our eventual return value
        $return = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'table_start',
                'widths' => array_values($widths),
            )
        );

        // loop through each row
        foreach ($table as $row) {
            // start a new row
            $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'row_start')
            );

            foreach ($row as $cell) {
                // start a new cell...
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array(
                        'type'   => 'cell_start',
                        'attr'   => $cell['attr'],
                        'span'   => $cell['span'],
                        'length' => $cell['length'],
                    )
                );

                // ...add the content...
                $return .= $cell['content'];

                // ...and end the cell.
                $return .= $this->wiki->addToken(
                    $this->rule,
                    array(
                        'type'   => 'cell_end',
                        'attr'   => $cell['attr'],
                        'span'   => $cell['span'],
                        'length' => $cell['length'],
                    )
                );
            }

            // end the row
            $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'row_end')
            );
        }

        // End the table.
        $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'table_end')
        );

        // we're done!
        return "\n$return\n\n";
    }
}
