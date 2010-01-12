<?php if (!empty($this->news)): ?>

<table>

<?php foreach ($this->news as $news_id => $news) { ?>
<tr>
<td>
<?php
echo strftime($GLOBALS['prefs']->getValue('date_format'), strtotime($news['publish'])) . ' - ';
echo Horde::link(News::getUrlFor('news', $news['id']), $news['abbreviation']);
echo $news['title'] . '</a>';
?>
</td>
</tr>

<?php } ?>
</table>

<?php else: ?>
    <p><?php echo _("There are no news to display.") ?></p>
<?php endif; ?>
