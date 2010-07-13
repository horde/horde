<?php
/**
 * News General View Class
 *
 * $Id: View.php 1260 2009-02-01 23:15:50Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_View extends Horde_View {

    /**
     * Constructor
     */
    public function __construct()
    {
        /* Set parents defualt data */
        parent::__construct(array('templatePath' => NEWS_TEMPLATES,
                                  'encoding' => $GLOBALS['registry']->preferredLang()));
    }

    /**
     * Formats time according to user preferences.
     *
     * @param int $timestamp  Message timestamp.
     *
     * @return string  Formatted date.
     */
    public function format_date($timestamp)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp);
    }

    /**
     * Formats time according to user preferences.
     *
     * @param int $timestamp  Message timestamp.
     *
     * @return string  Formatted date.
     */
    public function format_datetime($timestamp)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp)
            . ' '
            . (date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp));
    }

    /**
     * Link tags
     *
     * @param string $tags Video's tags
     */
    public function getTagsLinks($tags)
    {
        if (empty($tags)) {
            return '';
        }

        $html = '';
        $search = Horde::applicationUrl('search.php');
        foreach (explode(' ', $tags) as $tag) {
            $html .= '<a href="'
                    . Horde_Util::addParameter($search, array('word' => $tag))
                     . '">' . $tag . '</a> ';
        }

        return $html;
    }
}
