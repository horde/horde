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
<?php foreach ($users as $user): ?>
   <item>
    <title><?php echo htmlspecialchars($user) ?></title>
    <description><![CDATA[ <?php echo '<img src="' . Folks::getImageUrl($user, 'small', true) . '" />'?> ]]></description>
    <link><?php echo Folks::getUrlFor('user', $user, true) ?></link>
    <author><?php echo $user ?></author>
  </item>
  <?php endforeach; ?>
 </channel>
</rss>


