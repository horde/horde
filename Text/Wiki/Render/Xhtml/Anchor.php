<?php

/**
* 
* This class renders an anchor target name in XHTML.
*
* @author Manuel Holtgrewe <purestorm at ggnore dot net>
*
* @author Paul M. Jones <pmjones at ciaweb dot net>
*
* @package Text_Wiki
*
*/

class Text_Wiki_Render_Xhtml_Anchor extends Text_Wiki_Render {
	
	function token($options)
	{
		extract($options); // $type, $name
		
		if ($type == 'start') {
			return sprintf('<a id="%s">',$name);
		}
		
		if ($type == 'end') {
			return '</a>';
		}
	}
}

?>
