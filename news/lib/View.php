<?php
/**
 * News General View Class
 *
 * $Id: View.php 250 2008-01-18 15:31:32Z duck $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
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
                                  'encoding' => NLS::select()));

        $this->list_url = Horde::applicationUrl('list.php');
    }

    /**
     * Format value accoring to currency
     *
     * @param float   $price  The price value to format.
     *
     * @return Currency formatted price string.
     */
    public public function format_price($price)
    {
        if (empty($price)) {
            return '';
        }

        static $currency;

        if (is_null($currency)) {
            $currencies = new Horde_CurrenciesMapper();
            $currency = $currencies->getDefault('data');
        }

        return Horde_Currencies::formatPrice($price, $currency);
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
