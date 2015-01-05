<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Object handling storage of cached CSS data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
abstract class Horde_Themes_Css_Cache
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Process a list of CSS files.
     *
     * @param array $css       See Horde_Themes_Css#getStylesheets().
     * @param string $cacheid  Cache ID.
     *
     * @return array  The list of URLs to display (Horde_Url objects).
     */
    abstract public function process($css, $cacheid);

    /**
     * Perform garbage collection.
     */
    public function gc()
    {
    }

}
