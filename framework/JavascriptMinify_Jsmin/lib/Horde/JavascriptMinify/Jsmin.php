<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (JSMin).
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   JSMin
 * @package   JavascriptMinify
 */

/**
 * Native PHP javascript minification driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   JSMin
 * @package   JavascriptMinify_Jsmin
 */
class Horde_JavascriptMinify_Jsmin extends Horde_JavascriptMinify_Null
{
    /**
     */
    public function minify()
    {
        $jsmin = new Horde_JavascriptMinify_Jsmin_Minifier(parent::minify());
        return $jsmin->minify();
    }

}
