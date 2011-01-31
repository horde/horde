<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>

<?php echo '<?xml-stylesheet href="' . $xsl . '" type="text/xsl"?>' ?>

<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:georss="http://www.georss.org/georss">
 <channel>
  <title><?php echo $stream_name ?></title>
  <description><?php echo $stream_desc ?></description>
  <image>
   <url><?php echo $image_url ?></url>
   <link><?php echo $image_link ?></link>
   <title><?php echo $stream_name ?></title>
  </image>
  <link><?php echo $stream_official ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $stream_name ?>" href="<?php echo $stream_rss ?>" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
  <pubDate><?php echo $stream_updated ?></pubDate>
  <generator><?php echo $ansel ?></generator>
  <?php foreach($imgs as $image): ?>
  <item>
   <title><?php echo $image['filename'] ?></title>
   <description><?php echo htmlspecialchars('<img src="' . $image['url'] . '" />') . $image['caption'] ?></description>
   <link><?php echo htmlspecialchars($image['link']) ?></link>
   <?php if (!empty($image['author'])): ?>
   <author><?php echo $image['author'] ?></author>
   <?php endif; ?>
   <guid isPermaLink="true"><?php echo htmlspecialchars($image['link']) ?></guid>
   <media:content url="<?php echo $image['url'] ?>" type="<?php echo $image['type'] ?>"/>
   <media:thumbnail url="<?php echo $image['thumb'] ?>"/>
   <media:title type="plain"><?php echo $image['filename'] ?></media:title>
   <media:description type="html"><?php echo $image['caption'] ?></media:description>
   <?php if(!empty($image['latitude'])):?>
   <georss:point><?php echo $image['latitude'] . ' ' . $image['longitude']?></georss:point>
   <?php endif;?>
  </item>
  <?php endforeach; ?>
 </channel>
</rss>

