<?php
/**
 * The Horde_Themes_Element:: class provides an object-oriented interface to
 * a themes element.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Core
 */
class Horde_Themes_Element
{
    /**
     * The URI.
     *
     * @var string
     */
    public $uri = '';

    /**
     * The filesystem path.
     *
     * @var string
     */
    public $fs = '';

    /**
     * Constructor.
     *
     * @param string $uri  The image URI.
     * @param string $fs   The image filesystem path.
     */
    public function __construct($uri, $fs)
    {
        $this->uri = $uri;
        $this->fs = $fs;
    }

    /**
     * String representation of this object.
     *
     * @return string  The URI.
     */
    public function __toString()
    {
        return $this->uri;
    }

    /**
     * Convert a URI into a Horde_Themes_Image object.
     *
     * @param string $uri  The URI to convert.
     *
     * @return Horde_Themes_Image  An image object.
     */
    static public function fromUri($uri)
    {
        return new self($uri, realpath($GLOBALS['registry']->get('fileroot', 'horde')) . preg_replace('/^' . preg_quote($GLOBALS['registry']->get('webroot', 'horde'), '/') . '/', '', $uri));
    }

}
