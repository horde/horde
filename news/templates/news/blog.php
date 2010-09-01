<br />
<?php
echo '<div class="header">' . _("Blogs") . '</div>';

// Blogs
if (!empty($row['trackback'])) {
    echo _("Talkbacks to this article:") .  '<ul>';
    foreach ($row['trackback'] as $trackback) {
        echo '<li>' . Horde::link($trackback['url'], $trackback['excerpt']) . $trackback['title'] . '</a> '
             . _("From: ") . $trackback['blog_name'] . ' @ ' . News::dateFormat($trackback['created']) . '</li>';
    }
    echo '</ul><br />';
}

$trackback_url = Horde_Util::addParameter(Horde::url('trackback.php', true), 'id', $id);
echo _("Use the following link to trackback from your own site: ") .
    '<br> <input value="' . $trackback_url . '" /><br />';

if ($registry->hasMethod('blogs/createUrl')) {
    $intro = substr($plain_text, 0, 255) . '...';
    $blog_url = $registry->callByPackage('thomas', 'createUrl', array($trackback_url, $row['title'], $intro));
    echo Horde::link($blog_url) . '<strong>' . _("Trackback this blog on this site.") . '</strong></a><br/>';
}

$read_url = News::getUrlFor('news', $id);
?>

<!--
Auto-Discovery of TrackBack Ping URLs
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
    <rdf:Description
        rdf:about="<?php echo $read_url; ?>"
        dc:identifier="<?php echo $read_url; ?>"
        dc:title="<?php echo $row['title'] ?>"
        trackback:ping="<?php echo $trackback_url; ?>" />
</rdf:RDF>
-->
