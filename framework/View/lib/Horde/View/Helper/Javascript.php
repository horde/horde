<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Javascript extends Horde_View_Helper_Base
{
    public function escapeJavascript($javascript)
    {
        return str_replace(array('\\',   "\r\n", "\r",  "\n",  '"',  "'"),
                           array('\0\0', "\\n",  "\\n", "\\n", '\"', "\'"),
                           $javascript);
    }

    public function javascriptTag($content, $htmlOptions = array())
    {
        return $this->contentTag('script',
                                 $this->javascriptCdataSection($content),
                                 array_merge($htmlOptions, array('type' => 'text/javascript')));
    }

    public function javascriptCdataSection($content)
    {
        return "\n//" . $this->cdataSection("\n$content\n//") . "\n";
    }
}
