<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Renders a table for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Table2 extends Text_Wiki_Render
{
    /**
     * The cell spans.
     *
     * @var array
     */
    protected $_spans;

    /**
     * The current row.
     *
     * @var integer
     */
    protected $_row;

    /**
     * The current column.
     *
     * @var integer
     */
    protected $_col;

    /**
     * The number of the header row.
     *
     * reST only allows for one header.
     *
     * @var integer
     */
    protected $_header;

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options  The "options" portion of the token (second
     *                        element).
     *
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        switch ($options['type']) {
        case 'table_start':
            $this->_spans = array();
            $this->_row = 0;
            $this->_header = null;
            $this->wiki->registerRenderCallback(array($this, 'renderTable'));
            break;

        case 'row_start':
            $this->_col = 0;
            return "\0";

        case 'cell_start':
            if ($options['attr'] == 'header' && !isset($this->_header)) {
                $this->_header = $this->_row;
            }
            $this->_spans[$this->_row][$this->_col] = $options['span'];
            return "\1";

        case 'cell_end':
            $this->_col += $options['span'];
            break;

        case 'row_end':
            $this->_row++;
            break;

        case 'table_end':
            return $this->wiki->popRenderCallback();
        }
    }

    public function renderTable($text)
    {
        $lines = explode("\0", $text);
        array_shift($lines);

        // Calculate column widths.
        $widths = $rows = array();
        foreach ($lines as $lineno => $line) {
            $row = array();
            $cells = explode("\1", $line);
            array_shift($cells);
            $column = 0;
            foreach ($cells as $cellno => $cell) {
                // Record the column widths.
                $span = $this->_spans[$lineno][$column];
                if (isset($widths[$column])) {
                    $length = strlen($cell);
                    for ($j = $span; $j > 1; $j--) {
                        if (isset($widths[$cellno - $j + 1])) {
                            $length -= $widths[$cellno - $j + 1];
                        }
                    }
                    $widths[$column] = max($widths[$column], $length);
                } else {
                    $widths[$column] = ceil(strlen($cell) / $span);
                }
                $row[] = $cell;
                $column += $span;
            }
            $rows[] = $row;
        }

        // Generate the output.
        $output = '+';
        foreach ($widths as $width) {
            $output .= str_repeat('-', $width) . '+';
        }
        $output .= "\n";
        foreach ($rows as $rowno => $row) {
            $column = 0;
            foreach ($row as $cellno => $cell) {
                $output .= '|' . $cell;
                $span = $this->_spans[$rowno][$column];
                $width = $span - 1;
                for ($i = 0; $i < $span; $i++) {
                    $width += $widths[$column + $i];
                }
                $output .= str_repeat(' ', $width - strlen($cell));
                $column += $span;
            }
            $output .= "|\n+";
            foreach ($widths as $width) {
                $output .= str_repeat($rowno == $this->_header ? '=' : '-', $width) . '+';
            }
            $output .= "\n";
        }

        return $output;
    }
}
