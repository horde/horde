<?php
/**
 * News General View Class
 *
 * $Id: View.php 1118 2008-12-04 19:10:41Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
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
                                  'encoding' => NLS::select()));
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
    function getTagsLinks($tags)
    {
        if (empty($tags)) {
            return '';
        }

        $html = '';
        $search = Horde::applicationUrl('search.php');
        foreach (explode(' ', $tags) as $tag) {
            $html .= '<a href="'
                    . Util::addParameter($search, array('word' => $tag))
                     . '">' . $tag . '</a> ';
        }

        return $html;
    }
}