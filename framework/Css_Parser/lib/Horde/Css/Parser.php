<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Css_Parser
 */

/**
 * Horde interface to the Sabberworm CSS Parser library.
 *
 * Source: https://github.com/sabberworm/PHP-CSS-Parser
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Css_Parser
 */
class Horde_Css_Parser
{
    /**
     * The parsed CSS document object.
     *
     * @var Sabberworm\CSS\CSSList\Document
     */
    public $doc;

    /**
     * The parser object.
     *
     * @var Sabberworm\CSS\Parser
     */
    public $parser;

    /**
     * Constructor.
     *
     * @param string $css                       CSS data.
     * @param Sabberworm\CSS\Settings $charset  Parser settings.
     */
    public function __construct($css, Sabberworm\CSS\Settings $settings = null)
    {
        if (is_null($settings)) {
            $settings = Sabberworm\CSS\Settings::create();
            $settings->withMultibyteSupport(false);
        }

        $this->parser = new Sabberworm\CSS\Parser($css, $settings);
        $this->doc = $this->parser->parse();
    }

    /**
     * Returns compressed CSS output.
     *
     * @return string  Compressed CSS
     */
    public function compress()
    {
        return str_replace(
            array("\n", ' {', ': ', ';}', ', '),
            array('', '{', ':', '}', ','),
            strval($this->doc)
        );
    }

}
