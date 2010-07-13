<?php echo '<?xml version="1.0" encoding="' . $GLOBALS['registry']->getCharset() . '"?>' ?>

<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
 <channel>
  <title><?php echo $title ?></title>
  <description><?php echo $title ?></description>
  <image>
   <url><?php echo Horde_Themes::img('folks.png') ?></url>
   <link><?php echo $link ?></link>
   <title><?php echo $title ?></title>
  </image>
  <link><?php echo $link ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $title ?>" href="<?php echo $rss_link ?>" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
  <pubDate><?php echo htmlspecialchars(date('r')); ?></pubDate>
  <generator><?php echo $registry->get('name') ?></generator>
<?php foreach ($firendActivities as $activity_date => $activity): ?>
   <item>
    <title><?php echo htmlspecialchars($activity['user']) ?></title>
    <description><?php echo htmlspecialchars(strip_tags($activity['message'])) ?></description>
    <link><?php echo Folks::getUrlFor('user', $activity['user'], true) ?></link>
    <author><?php echo $activity['user'] ?></author>
    <pubDate><?php echo htmlspecialchars(date('r'), $activity_date); ?></pubDate>
  </item>
  <?php endforeach; ?>
 </channel>
</rss>

