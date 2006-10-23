<?php

/**
 *
 * Parses for table markup.
 *
 * This class implements a Text_Wiki_Parse to find source text marked as
 * a set of table rows, where a line start (and optionally ends) with a
 * single-pipe (|) and uses single-pipes to separate table cells.
 * The rows must be on sequential lines (no blank lines between them).
 * A blank line indicates the beginning of other text or another table.
 *
 * @category Text
 *
 * @package Text_Wiki
 *
 * @author Michele Tomaiuolo <tomamic@yahoo.it>
 * @author Paul M. Jones <pmjones@php.net>
 *
 * @license LGPL
 *
 * @version $Id$
 *
 */


class Text_Wiki_Parse_Table extends Text_Wiki_Parse {


    /**
     *
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     *
     * @var string
     *
     * @see parse()
     *
     */

    var $regex = '/\n((\|).*)(\n)(?!(\|))/Us';


    /**
     *
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
     * @access public
     *
     * @param array &$matches The array of matches from parse().
     *
     * @return A series of text and delimited tokens marking the different
     * table elements and cell text.
     *
     */

    function process(&$matches)
    {
        // our eventual return value
        $return = '';

        // the number of columns in the table
        $num_cols = 0;

        // the number of rows in the table
        $num_rows = 0;

        // rows are separated by newlines in the matched text
        $rows = explode("\n", $matches[1]);

        // loop through each row
        foreach ($rows as $row) {

            // increase the row count
            $num_rows ++;

            // start a new row
            $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'row_start')
            );

            // cells are separated by pipes
            $cell = explode("|", $row);

            // get the number of cells (columns) in this row
            $last = count($cell) - 1;

            // is this more than the current column count?
            // (we decrease by 1 because we never use cell zero)
            if ($last - 1 > $num_cols) {
                // increase the column count
                $num_cols = $last - 1;
            }

            // by default, cells span only one column (their own)
            $span = 1;
            $attr = '';

            // ignore cell zero, and ignore the "last" cell; cell zero
            // is before the first pipe, and the "last" cell is
            // after the last pipe. the last one can have content.
            for ($i = 1; $i < $last || ($i == $last && strlen(trim($cell[$i])) > 0); $i ++) {

                if (strlen($cell[$i]) == 0) {
                    $attr = 'header';
                }
                else {

                    // start a new cell...
                    $return .= $this->wiki->addToken(
                        $this->rule,
                        array (
                            'type' => 'cell_start',
                            'attr' => $attr,
                            'span' => $span
                        )
                    );

                    // ...add the content...
                    $return .= trim($cell[$i]);

                    // ...and end the cell.
                    $return .= $this->wiki->addToken(
                        $this->rule,
                        array (
                            'type' => 'cell_end',
                            'attr' => $attr,
                            'span' => $span
                        )
                    );

                    // reset the span.
                    $span = 1;
                    $attr = '';
                }
            }

            // end the row
            $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'row_end')
            );

        }

        // wrap the return value in start and end tokens
        $return =
            $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'table_start',
                    'rows' => $num_rows,
                    'cols' => $num_cols
                )
            )
            . $return .
            $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'table_end'
                )
            );

        // we're done!
        return "\n\n$return\n\n";
    }
}
?>