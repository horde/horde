<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<?php echo '<?xml-stylesheet href="' . $this->xsl . '" type="text/xsl"?>' ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
 <channel>
  <title><?php echo $this->h($this->title) ?></title>
  <pubDate><?php echo $this->h($this->pubDate) ?></pubDate>
  <link><?php echo $this->h($this->url) ?></link>
  <atom:link rel="self" type="application/rss+xml" title="<?php echo $this->h($this->title) ?>" href="<?php echo $this->h($this->rss_url) ?>" />
  <description><?php echo $this->h($this->desc) ?></description>

<?php foreach ($this->items as $v): ?>
  <item>
   <title><?php echo $v['title'] ?></title>
   <description><![CDATA[
    <strong>Date:</strong> <?php echo $v['pubDate'] ?><br />
    <strong>From:</strong> <?php echo $v['fromAddr'] ?><br />
    <strong>To:</strong> <?php echo $v['toAddr'] ?><br />
    <pre><?php echo $v['description'] ?></pre>
   ]]></description>
   <pubDate><?php echo $v['pubDate'] ?></pubDate>
   <guid isPermaLink="true"><?php echo $v['url'] ?></guid>
  </item>
<?php endforeach; ?>

 </channel>
</rss>
