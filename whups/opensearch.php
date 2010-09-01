<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

// Url.
$url = Horde::url('', true);

// Name.
$name = $registry->get('name', 'whups') . ' (' . $url . ')';

// Icon.
$icon = base64_encode(file_get_contents($registry->get('themesfs', 'whups') . '/graphics/whups.png'));

// Charset.
$charset = $GLOBALS['registry']->getCharset();

header('Content-Type: text/xml; charset=' . $charset);
echo <<<PAYLOAD
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName>$name</ShortName>
  <SearchForm>$url</SearchForm>
  <Url type="text/html"
       method="get"
       template="${url}ticket/">
    <Param name="id" value="{searchTerms}"/>
  </Url>
  <Image height="16" width="16">data:image/png;base64,$icon</Image>
  <InputEncoding>$charset</InputEncoding>

</OpenSearchDescription>
PAYLOAD;
