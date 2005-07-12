<?php
/**
* @version Exp $
* 
* BBCode: Parse redefinition to parse nested components
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Bertrand Gugger <bertrand@toggg.com>
* 
*/
/**
* 
* Baseline rule class for BBCode parser.
* 
* @see Text_Wiki_Parse
* 
* The parse() method enables the nesting of components
* by checking the done property (default true)
* 
* A process() method needing to "rerun" has to set it to false
* 
*/

class Text_Wiki_Parse_BBCode extends Text_Wiki_Parse {
    
    
    /**
    * 
    * Flag indicating to the parse method to re-parse the source for nesting
    * 
    * @access public
    * 
    * @var boolean
    * 
    */
    var $done = true;
    
    /**
    * 
    * Flag indicating to the parse method to synchronize nested start and end tags
    * by calling the custom method synchStartEnd() for each corresponding pair of them
    * 'type'=>'start' or 'type'=>'end' are then mandatory for this rule's tokens
    * 
    * @access public
    * 
    * @var boolean
    * 
    */
    var $synch = false;
    
    /**
    * 
    * Constructor for this parser rule.
    * 
    * @access public
    * 
    * @param object &$obj The calling "parent" Text_Wiki object.
    * 
    */
    
    function Text_Wiki_Parse_BBCode(&$obj)
    {
        parent::Text_Wiki_Parse($obj);
        $this->synch = method_exists($this, 'synchStartEnd');
    }
    
    
    /**
    * 
    * Abstract method to parse source text for matches.
    *
    * Repeats the parent parse method until done
    * Nesting rules process() can set this flag to false for redo
    *
    * @access public
    * 
    * @see Text_Wiki_Parse::parse()
    * 
    */
/*    
    function parse()
    {
        do {
            $this->done = true;
            parent::parse();
        } while (!$this->done);
    }
*/    
    function parse()
    {
        if ($this->synch) {
            $loop = -1;
            do {
                $this->done = true;
                $tokno = $this->wiki->addToken(
                    $this->rule, array('type'=>'parseStart'.$loop), 'id_only');
                parent::parse();
                $tokend = $this->wiki->addToken(
                    $this->rule, array('type'=>'parseEnd'.$loop), 'id_only');
                $pos = $lev = 0;
                $stapos = array();
                $statok = array();
                while (++$tokno < $tokend) {
                    $find = $this->wiki->delim . $tokno . $this->wiki->delim;
                    $newpos = strpos($this->wiki->source, $find, $pos);
                    if ($this->wiki->tokens[$tokno][1]['type'] == 'start') {
                        $stapos[$lev] = $newpos + strlen($find);
                        $statok[$lev++] = $tokno;
                        continue;
                    }
                    // we assume type is 'end'
                    $this->synchStartEnd(
                        $this->wiki->tokens[array_pop($statok)][1],
                        $this->wiki->tokens[$tokno][1],
                        array_pop($stapos), $newpos);
                    $pos = $newpos + strlen($find);
                    $lev--;
                }
            } while (!$this->done);
        } else {
            do {
                $this->done = true;
                parent::parse();
            } while (!$this->done);
        }
    }
}
?>
