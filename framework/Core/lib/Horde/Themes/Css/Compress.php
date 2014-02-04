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
 * @package   Core
 */

/**
 * Compresses CSS based on Horde configuration parameters.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Themes_Css
{
    /**
     * Loads CSS files, cleans up the input (and compresses), and concatenates
     * to a string.
     *
     * @param array $css  See Horde_Themes_Css#getStylesheets().
     *
     * @return string  CSS data.
     */
    public function compress($css)
    {
        global $browser, $conf;

        $dataurl = (empty($conf['nobase64_img']) &&
                    $browser->hasFeature('dataurl'));
        $out = '';

        foreach ($css as $file) {
            $data = file_get_contents($file['fs']);
            $path = substr($file['uri'], 0, strrpos($file['uri'], '/') + 1);
            $url = array();

            try {
                $css_parser = new Horde_Css_Parser($data);
            } catch (Exception $e) {
                /* If the CSS is broken, log error and output as-is. */
                Horde::log($e, 'ERR');
                $out .= $data;
                continue;
            }

            foreach ($css_parser->doc->getContents() as $val) {
                if ($val instanceof Sabberworm\CSS\Property\Import) {
                    $ob = Horde_Themes_Element::fromUri($path . $val->getLocation()->getURL()->getString());
                    $out .= $this->loadCssFiles(array(array(
                        'app' => null,
                        'fs' => $ob->fs,
                        'uri' => $ob->uri
                    )));
                    $css_parser->doc->remove($val);
                }
            }

            foreach ($css_parser->doc->getAllRuleSets() as $val) {
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

                if (Horde_Url_Data::isData($url_str)) {
                    $url_ob->setString($url_str);
                } else {
                    if ($dataurl) {
                        /* Limit data to 16 KB in stylesheets. */
                        $url_ob->setString(Horde_Themes_Image::base64ImgData($path . $url_str, 16384));
                    } else {
                        $url_ob->setString($path . $url_str);
                    }
                }
            }

            $out .= $css_parser->compress();
        }

        return $out;
    }

}
