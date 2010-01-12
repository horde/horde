<p style="clear: both;">
<img src="<?php echo News::getImageUrl($row['id'], 'small'); ?>" style="float: left; margin: 5px;" />
<strong>
<?php echo Horde::link(News::getUrlFor('news', $row['id']), $row['title']) . $row['title'] . '</a>'; ?>
</strong> - <?php echo News::dateFormat($row['publish'], false) ?>
 <br />
<?php echo $row['abbreviation'] ?> ...<br />
<span class="small">
<?php echo _("Chars") . ': ' . number_format($row['chars']) . ' | '; ?>
<?php echo _("Comments") . ': ' . $row['comments']; ?>
</span>
</p>