<form method="get" action="<?php echo Horde::selfUrl(false, false) ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="<?php echo $actionID ?>" />
<input type="hidden" name="date" value="<?php echo $date ?>" />

<p class="control">
<?php
if ($actionID == 'comic') {
    if ($comic_select) {
        echo '<span class="rightFloat">' . $comic_select . '</span>';
    }
    if (!is_null($prev_month_url)) {
        echo Horde::link($prev_month_url) . Horde::img('nav/left.png') . '</a>';
    } else {
        echo Horde::img('nav/left-grey.png');
    }
    echo ' ' . strftime('%B %Y', $date) . ' ';
    if (!is_null($next_month_url)) {
        echo Horde::link($next_month_url) . Horde::img('nav/right.png') . '</a>';
    } else {
        echo Horde::img('nav/right-grey.png');
    }
} elseif ($actionID == 'day') {
    if (!is_null($yesterday_url)) {
        echo Horde::link($yesterday_url) . Horde::img('nav/left.png') . '</a>';
    } else {
        echo Horde::img('nav/left-grey.png');
    }
    echo ' ' . strftime($prefs->getValue('date_format'), $date) . ' ';
    if (!is_null($tomorrow_url)) {
        echo Horde::link($tomorrow_url) . Horde::img('nav/right.png') . '</a>';
    } else {
        echo Horde::img('nav/right-grey.png');
    }
}
?>
</p>
</form>
