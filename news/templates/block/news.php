<?php if (!empty($this->news)): ?>

<?php foreach ($this->news as $news_id => $news) { ?>
<p>
<?php
echo Horde::link(News::getUrlFor('news', $news['id']), $news['title']);
if ($news['picture']) {
    echo Horde::img(News::getImageUrl($news['id']), $news['title'], 'style="float: left; padding-right: 3px;"','');
}
echo '<strong>' . $news['title'] . '</a></strong> - ' . $news['abbreviation'] . '... (' . $news['comments'] . ')<br /> ';
?>
</p>

<?php } ?>


<?php else: ?>
    <p><?php echo _("There are no news to display.") ?></p>
<?php endif; ?>