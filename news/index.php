<?php
/**
 *
 * $Id: index.php 1162 2009-01-14 11:00:29Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
$news_configured = (is_readable(NEWS_BASE . '/config/conf.php') &&
                    is_readable(NEWS_BASE . '/config/prefs.php'));

if (!$news_configured) {
    require NEWS_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('news', NEWS_BASE,
                                   array('conf.php', 'prefs.php', 'sources.php'));
}

require NEWS_BASE . '/content.php';
