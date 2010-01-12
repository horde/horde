<div class="pagebody">
<?php
/**
 * Formats time according to user prefs
 *
 * @param int $timestamp message timestamp
 *
 * @return string $date formatted date
 */
function _format_date($timestamp)
{
    return strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp)
        . ' ' . date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp);
}

echo $form;

// notify what form returned
$notification->notify(array('listeners' => 'status'));
