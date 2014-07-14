<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   CssMinify
 */

/**
 * CSS minification driver implemented by using the Horde/Css_Parse library.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   CssMinify
 */
class Horde_CssMinify_CssParser extends Horde_CssMinify
{
    /**
     * @param array $opts  Driver specific options:
     * <pre>
     *   - dataurl: (callback) A callback function to convert a URI to a
     *              data URL. Takes one argument (URI) and returns the data
     *              URI to be used in the file.
     *   - import: (callback) A callback function to convert a URI to a
     *             pathname. Takes one argument (URI) and expects an array
     *             back with two elements (URI, filename).
     * </pre>
     */
    public function setOptions(array $opts = array())
    {
        parent::setOptions($opts);
    }

    /**
     */
    public function minify()
    {
        if (is_string($this->_data)) {
            $parser = new Horde_Css_Parser($this->_data);
            return $parser->compress();
        }

        return $this->_minify($this->_data);
    }

    /**
     */
    protected function _minify($data)
    {
        $out = '';

        foreach ($data as $uri => $file) {
            if (!is_readable($file)) {
                $this->_opts['logger']->log(
                    sprintf('Could not open CSS file %s.', $file),
                    Horde_Log::ERR
                );
                continue;
            }

            $css = file_get_contents($file);

            try {
                $parser = new Horde_Css_Parser($css);
            } catch (Exception $e) {
                /* If the CSS is broken, log error and output as-is. */
                $this->_opts['logger']->log($e, Horde_Log::ERR);
                $out .= $css;
                continue;
            }

            if (!empty($this->_opts['import'])) {
                foreach ($parser->doc->getContents() as $val) {
                    if ($val instanceof Sabberworm\CSS\Property\Import) {
                        $res = call_user_func($this->_opts['import'], dirname($uri) . '/' . $val->getLocation()->getURL()->getString());
                        $out .= $this->_minify(array($res[0] => $res[1]));
                        $parser->doc->remove($val);
                    }
                }
            }

            $url = array();
            foreach ($parser->doc->getAllRuleSets() as $val) {
                foreach ($val->getRules('background-') as $val2) {
                    $item = $val2->getValue();

                    if ($item instanceof Sabberworm\CSS\Value\URL) {
                        $url[] = $item;
                    } elseif ($item instanceof Sabberworm\CSS\Value\RuleValueList) {
                        foreach ($item->getListComponents() as $val3) {
                            if ($val3 instanceof Sabberworm\CSS\Value\URL) {
                                $url[] = $val3;
                            }
                        }
                    }
                }
            }

            foreach ($url as $val) {
                $url_ob = $val->getURL();
                $url_str = $url_ob->getString();

                if (!Horde_Url_Data::isData($url_str)) {
                    $url_str = dirname($uri) . '/' . $url_str;
                    if (!empty($this->_opts['dataurl'])) {
                        $url_str = call_user_func($this->_opts['dataurl'], $url_str);
                    }
                }

                $url_ob->setString($url_str);
            }

            $out .= $parser->compress();
        }

        return $out;
    }

}
