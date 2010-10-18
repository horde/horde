<?php
/**
 * Interface describing a translation factory.
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
 * Interface describing a translation factory.
 *
 * @category  Horde
 * @package   Translation
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @license   http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link      http://pear.horde.org/index.php?package=Translation
 */
interface Horde_Translation_Factory
{
    /**
     * Returns a translation handler. The relative path to the domains locale
     * data will be preferred if the absolute path indicates that it is unset.
     *
     * @param string $domain   The domain of the translation handler.
     * @param string $absolute The absolute path to the locale data for
     *                         this handler.
     * @param string $relative The relative path to the locale data for
     *                         this handler.
     *
     * @return Horde_Translation The translation handler.
     */
    public function createTranslation($domain, $absolute, $relative);
}
