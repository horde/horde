<br />
<h1 class="header"><?php echo $row['title'] ?></h1>
<table style="width: 100%">
<tr valign="top">
<td>
<?php

if ($row['picture'])  {
    echo '<div id="news_picture">'
            . '<a href="' . News::getImageUrl($id, 'full') . '" title="' . _("Click for full picture") . '" target="_blank">'
            . '<img src="' . News::getImageUrl($id, 'big') . '" /></a><br />'
            . $row['picture_comment']
            . '</div>';
}

$plain_text = trim(strip_tags($row['content']));
echo '<div id="capital">' . substr($plain_text, 0, 1) . '</div>';
echo $row['content'];

if ($row['sponsored']) {
    echo '<span class="sposored">' . _("* Sponsored news") . '</span>';
}

require NEWS_TEMPLATES . '/news/parents.php';
require NEWS_TEMPLATES . '/news/attachments.php';
require NEWS_TEMPLATES . '/news/ulaform.php';
require NEWS_TEMPLATES . '/news/selling.php';
require NEWS_TEMPLATES . '/news/gallery.php';
require NEWS_TEMPLATES . '/news/threads.php';
require NEWS_TEMPLATES . '/news/comments.php';

?>
</td>
<td style="border-left: 1px solid #cccccc; padding-left: 5px; width: 150px;">
<?php

require NEWS_TEMPLATES . '/news/info.php';
require NEWS_TEMPLATES . '/news/today.php';
require NEWS_TEMPLATES . '/news/blog.php';
require NEWS_TEMPLATES . '/news/tools.php';
require NEWS_TEMPLATES . '/news/mail.php';

?>
</td>
</tr>
</table>