<?php
$bookmarklet_addurl = Horde::url(Horde_Util::addParameter('add.php', 'popup', 1), true, -1);
$bookmarklet_url = "javascript:d = new Date(); w = window.open('$bookmarklet_addurl' + '&amp;title=' + encodeURIComponent(document.title) + '&amp;url=' + encodeURIComponent(location.href) + '&amp;d=' + d.getTime(), d.getTime(), 'height=200,width=400'); w.focus();";
$bookmarklet_link = '<a href="' . $bookmarklet_url . '">' . Horde::img('add.png') . _("Add to Bookmarks") . '</a>';
