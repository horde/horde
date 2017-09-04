<?php

/**
 * 
 * Parses for table markup.
 * 
 * @category Text
 * 
 * @package Text_Wiki
 * 
 * @author Paul M. Jones <pmjones@php.net>
 * 
 * @license LGPL
 * 
 * @version $Id$
 * 
 */

/**
 * 
 * Parses for table markup.
 * 
 * This class implements a Text_Wiki_Parse to find source text marked as a
 * set of table rows, where a line start and ends with double-pipes (||)
 * and uses double-pipes to separate table cells.  The rows must be on
 * sequential lines (no blank lines between them) -- a blank line
 * indicates the beginning of a new table.
 *
 * @category Text
 * 
 * @package Text_Wiki
 * 
 * @author Paul M. Jones <pmjones@php.net>
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
    
    var $regex = '/((\n|^)[\|\^][^\n]+[\|\^]\n([\|\^][^\n]+[\|\^](\n|$))*)/s';
    
    
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
        $rows = explode("\n", trim($matches[1]));
        
        // loop through each row
        foreach ($rows as $row) {
            
            // increase the row count
            ++$num_rows;
            
            // start a new row
            $return .= $this->wiki->addToken(
                $this->rule,
                array('type' => 'row_start')
            );
            
            // cells are separated by pipes
            $cells = preg_split('/([\|\^])/', $row, -1, PREG_SPLIT_OFFSET_CAPTURE);
            /*echo $row;
            foreach ($cells as $num => $cell) {
                echo '<br/>'.$num.' '.$cell[1].' '.$row[$cell[1] - 1].' '.$cell[0].'<br/>';
            }
            print_R($cells);
            exit;*/
            
            // get the number of cells (columns) in this row
            $last = count($cells);
            
            // is this more than the current column count?
            if ($last - 2 > $num_cols) {
                // increase the column count
                $num_cols = $last - 2;
            }
            
            // by default, cells span only one column (their own)
            $span = 0;
            $cell = null;
            
            //0 should always be nothing
            for ($i = 0; $i < $last; ++$i) {
                
                // if there is no content at all, then it's an instance
                // of two sets of || next to each other, indicating a
                // span.
                if ($cells[$i][0] == '') {
                    
                    // add to the span and loop to the next cell
                    $span += 1;
                    continue;
                    
                } else {

                    if ($cell !== null) {
                        $return .= $this->cellToken($row, $cell, $span);
                    }

                    // reset the span.
                    $span = 0;
                    
                    // this cell has content.
                    $cell = $cells[$i];
                    $j = $i;
                }
            }

            if ($cell !== null) {
                $return .= $this->cellToken($row, $cell, $span);
            }
            $span = 0;
            $cell = null;
            
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
        return "\n$return\n\n";
    }

    function cellToken($row, $cell, $span) {
        $return = '';
        $attr = '';
        if ($row[$cell[1] - 1] == '^') {
            $attr = 'header';
        } else {
            $right = substr($cell[0], 0, 2) == '  ';
            $left = substr($cell[0], -2) == '  ';
            if ($right && $left) {
                $attr = 'center';
            } elseif ($right) {
                $attr = 'right';
            } elseif ($left) {
                $attr = 'left';
            }
        }

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
        $return .= trim($cell[0]);
                    
        // ...and end the cell.
        $return .= $this->wiki->addToken(
                                         $this->rule, 
                                         array (
                                                'type' => 'cell_end',
                                                'attr' => $attr,
                                                'span' => $span
                                                )
                                         );
        return $return;
    }
}
?>