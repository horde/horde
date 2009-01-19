<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Gettext
 * 
 * PHP versions 4 and 5
 *
 * @category   FileFormats
 * @package    File_Gettext
 * @author     Michael Wallner <mike@php.net>
 * @copyright  2004-2005 Michael Wallner
 * @license    BSD, revised
 * @version    CVS: $Id: PO.php,v 1.6 2006/01/07 09:45:25 mike Exp $
 * @link       http://pear.php.net/package/File_Gettext
 */

/**
 * Requires File_Gettext
 */
require_once dirname(__FILE__) . '/../Gettext.php';

/** 
 * File_Gettext_PO
 *
 * GNU PO file reader and writer.
 * 
 * @author      Michael Wallner <mike@php.net>
 * @version     $Revision: 1.6 $
 * @access      public
 */
class File_Gettext_PO extends File_Gettext
{
    /**
     * Constructor
     *
     * @access  public
     * @return  object      File_Gettext_PO
     * @param   string      path to GNU PO file
     */
    function File_Gettext_PO($file = '')
    {
        $this->file = $file;
    }

    /**
     * Load PO file
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     * @param   string  $file
     */
    function load($file = null)
    {
        $this->strings = array();
        
        if (!isset($file)) {
            $file = $this->file;
        }
        
        // load file
        if (!$contents = @file($file)) {
            return parent::raiseError($php_errormsg . ' ' . $file);
        }
	
        $contents = explode("\n", implode('', $contents));
	
	$state = 0;
	$comment = '';
	
	foreach($contents as $line) {
	    if( substr($line,0,1) == "#" ) {
		$comment .= "$line\r\n";
		continue;
	    }
	    
	    switch($state) {
	     case 0:
		if( preg_match( '/^msgid\s+"(.*)"/', $line, $container ) ) {
		    $state = 1;
		    $msgid = $container[1];
		    continue;
		}
		break;
		
	     case 1:
		if( preg_match( '/^msgstr\s+"(.*)"/', $line, $container ) ) {
		    $msgstr = $container[1];
		    $state = 2;
		} else {
		    $line = preg_replace( '/^"|"$/', "", $line );
		    $msgid .= $line;
		}
		continue;
		break;
		
	     case 2:
		if( preg_match( '/^\s*$/', $line ) ) {
		    if( $msgid != "" ) {
			$this->status[parent::prepare($msgid)] = $this->status2array($comment);
			$this->ref[parent::prepare($msgid)] = $this->ref2array($comment);
			
			// $msgid = @preg_replace('/\s*msgid\s*"(.*)"\s*/s', '\\1', $matches[1][$i]);
			// $msgstr= @preg_replace('/\s*msgstr\s*"(.*)"\s*/s', '\\1', $matches[4][$i]);
			
			if( $msgstr == "" ) {
			    $this->status[parent::prepare($msgid)][] = 'untranslated';
			} elseif (!in_array('fuzzy', $this->status[parent::prepare($msgid)])) {
			    $this->status[parent::prepare($msgid)][] = 'translated';
			}

			$this->strings[parent::prepare($msgid)] = parent::prepare($msgstr);
			$this->encstr[base64_encode(parent::prepare($msgid))] = parent::prepare($msgid);
			$comment = preg_replace('/\r/', '', $comment);
			$this->comments[parent::prepare($msgid)] = $comment;

		    } else {
			$this->meta = parent::meta2array(parent::prepare($msgstr));
		    }
		    $comment = "";
		    $state = 0;
		} else {
		    $line = preg_replace( '/^"|"$/', "", $line );
		    $msgstr .= $line;
		}
		break;
	    }
	}

        return true;
    }

    
    function status2array($comment) {
	$status = array();
	$comment = explode("\n", $comment);
	foreach($comment as $c) {
	    if (preg_match('/#,\s(.*)/', $c, $matches)) {
		$st = preg_split('/,/', trim($matches[1]));
		foreach($st as $s) {
		    $status[] = trim($s);
		}
	    }
	}
	return $status;
    }
    
    function ref2array($comment) {
	$refs = array();
	$comment = explode("\n", $comment);
	foreach($comment as $c) {
	    if (preg_match('/#:\s(.*)/', $c, $matches)) {
		$st = preg_split('/\s/', trim($matches[1]));
		foreach($st as $s) {
		    $refs[] = trim($s);
		}
	    }
	}
	return $refs;
    }
    
    /**
     * Save PO file
     *
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     * @param   string  $file
     */
    function save($file = null)
    {
        if (!isset($file)) {
            $file = $this->file;
        }
        
        // open PO file
        if (!is_resource($fh = @fopen($file, 'w'))) {
            return parent::raiseError($php_errormsg . ' ' . $file);
        }
        // lock PO file exclusively
        if (!@flock($fh, LOCK_EX)) {
            @fclose($fh);
            return parent::raiseError($php_errmsg . ' ' . $file);
        }
        
        // write meta info
        if (count($this->meta)) {
            $meta = 'msgid ""' . "\nmsgstr " . '""' . "\n";
            foreach ($this->meta as $k => $v) {
                $meta .= '"' . $k . ': ' . $v . '\n"' . "\n";
            }
            fwrite($fh, $meta . "\n");
        }
        // write strings
        foreach ($this->strings as $o => $t) {
	    $c = @$this->comments[$o];
            fwrite($fh,
		 $c . "\n" .
                'msgid "'  . parent::prepare($o, true) . '"' . "\n" .
                'msgstr "' . parent::prepare($t, true) . '"' . "\n\n"
            );
        }
        
        //done
        @flock($fh, LOCK_UN);
        @fclose($fh);
        return true;
    }
}
?>
