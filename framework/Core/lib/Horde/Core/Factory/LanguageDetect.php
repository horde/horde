<?php
/**
 * A Horde_Injector:: based Text_LanguageDetect:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Text_LanguageDetect:: factory.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_LanguageDetect extends Horde_Core_Factory_Base
{
    /**
     * A Text_LanguageDetect instance.
     *
     * @var Text_LanguageDetect
     */
    protected $_detect;

    /**
     * Mapping of language -> ISO 639 language codes.
     * Language list accurate as of Text_LanguageDetect v0.2.3.
     *
     * @var array
     */
    protected $_langmap = array(
        'albanian' => 'sq',
        'arabic' => 'ar',
        // azeri
        'bengali' => 'bn',
        'bulgarian' => 'bg',
        // cebuano
        'croatian' => 'hr',
        'czech' => 'cs',
        'danish' => 'da',
        'dutch' => 'nl',
        'english' => 'en',
        'estonian' => 'et',
        // farsi
        'finnish' => 'fi',
        'french' => 'fr',
        'german' => 'de',
        'hausa' => 'ha',
        // hawaiian
        'hindi' => 'hi',
        'hungarian' => 'hu',
        'icelandic' => 'is',
        'indonesian' => 'id',
        'italian' => 'it',
        'kazakh' => 'kk',
        'kyrgyz' => 'ky',
        'latin' => 'la',
        'latvian' => 'lv',
        'lithuanian' => 'lt',
        'macedonian' => 'mk',
        'mongolian' => 'mn',
        'nepali' => 'ne',
        'norwegian' => 'no',
        'pashto' => 'ps',
        // pidgin
        'polish' => 'pl',
        'portuguese' => 'pt',
        'romanian' => 'ro',
        'russian' => 'ru',
        'serbian' => 'sr',
        'slovak' => 'sk',
        'slovene' => 'sl',
        'somali' => 'so',
        'spanish' => 'es',
        'swahili' => 'sw',
        'swedish' => 'sv',
        'tagalog' => 'tl',
        'turkish' => 'tr',
        'ukrainian' => 'uk',
        'urdu' => 'ur',
        'uzbek' => 'uz',
        'vietnamese' => 'vi',
        'welsh' => 'cy'
    );

    /**
     * Return a Text_LanguageDetect instance.
     *
     * @return Text_LanguageDetect  Detection object.
     *
     * @throws Horde_Exception
     */
    public function create()
    {
        if (!isset($this->_detect)) {
            if (!class_exists('Text_LanguageDetect')) {
                throw new Horde_Exception('Language detection not available.');
            }
            $this->_detect = new Text_LanguageDetect();
        }

        return $this->_detect;
    }

    /**
     * Utility method to scan a string and return the appropriate langauge
     * code of the detected language.
     *
     * @param string $text  Input text.
     *
     * @return string  The ISO 639 language code (or null if it could not be
     *                 determined).
     */
    public function getLanguageCode($text)
    {
        $lang = $this->create()->detectSimple($text);

        return (!is_null($lang) && isset($this->_langmap[$lang]))
            ? $this->_langmap[$lang]
            : null;
    }

}
