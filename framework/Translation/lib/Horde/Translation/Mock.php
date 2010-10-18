<?php
/**
 * A mock translation that does not translate anything.
 *
 * PHP version 5
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category  Horde
 * @package   Translation
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @license   http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link      http://pear.horde.org/index.php?package=Translation
 */

/**
 * A mock translation that does not translate anything.
 *
 * @category  Horde
 * @package   Translation
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @license   http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link      http://pear.horde.org/index.php?package=Translation
 */
class Horde_Translation_Mock implements Horde_Translation
{
    /**
     * Returns the translation of a message.
     *
     * @param string $message  The string to translate.
     *
     * @return string  The string translation, or the original string if no
     *                 translation exists.
     */
    public function t($message)
    {
        return $message;
    }

    /**
     * Returns the plural translation of a message.
     *
     * @param string $singular  The singular version to translate.
     * @param string $plural    The plural version to translate.
     * @param integer $number   The number that determines singular vs. plural.
     *
     * @return string  The string translation, or the original string if no
     *                 translation exists.
     */
    public function ngettext($singular, $plural, $number)
    {
        return $number > 1 ? $plural : $singular;
    }
}
