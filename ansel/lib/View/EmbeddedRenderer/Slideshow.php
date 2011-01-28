<?php
/**
 * Ansel_View_EmbeddedRenderer_Slideshow
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_EmbeddedRenderer_Slideshow extends Ansel_View_Gallery
{
    /**
     * Create a new renderer.
     *
     * @see Ansel_View_Embedded
     *
     * @param array $params
     *
     * @return Ansel_View_EmbeddedRenderer  The renderer object.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    /**
     * Build the javascript that will render the view.
     *
     * @return string  A string containing valid javascript.
     */
    public function html()
    {

    }

}
