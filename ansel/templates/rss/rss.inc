<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>

<?php echo '<?xml-stylesheet href="' . $xsl . '" type="text/xsl"?>' ?>

<rss version="0.91">
 <channel>
  <title><?php echo $stream_name ?></title>
  <description><?php echo $stream_desc ?></description>
  <image>
   <url><?php echo $image_url ?></url>
   <link><?php echo $image_link ?></link>
   <title><?php echo $stream_desc ?></title>
  </image>
  <link><?php echo $stream_official ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $stream_name ?>" href="<?php echo $stream_rss ?>" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
  <pubDate><?php echo $stream_updated ?></pubDate>
  <?php foreach($imgs as $image): ?>
  <item>
   <title><?php echo $image['filename'] ?></title>
   <description><?php echo htmlspecialchars('<img src="' . $image['url'] . '" />') . $image['caption'] ?></description>
   <link><?php echo htmlspecialchars($image['link']) ?></link>
  </item>
  <?php endforeach; ?>
 </channel>
</rss>

