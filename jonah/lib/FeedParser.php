<?php
/**
 * Jonah_FeedParser.
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * @deprecated Will be removed once the Aggregator app (Hippo) is started.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
class Jonah_FeedParser {

    /**
     * XML parser resource.
     *
     * @var resource
     */
    var $parser;

    /**
     * The current parent tag - CHANNEL, STORY, etc.
     *
     * @var string
     */
    var $parent = '';

    /**
     * The current child tag - TITLE, DESCRIPTION, URL, etc.
     *
     * @var string
     */
    var $child = '';

    /**
     * All the attributes of the channel description.
     *
     * @var array
     */
    var $channel;

    /**
     * All the attributes of the channel image.
     *
     * @var array
     */
    var $image;

    /**
     * All the attributes of the current story being parsed.
     *
     * @var array
     */
    var $story;

    /**
     * All the attributes of the current item being parsed.
     *
     * @var array
     */
    var $item;

    /**
     * The array that all the parsed information gets dumped into.
     *
     * @var array
     */
    var $structure;

    /**
     * What kind of feed are we parsing?
     *
     * @var string
     */
    var $format = 'rss';

    /**
     * Error string.
     *
     * @var string
     */
    var $error;

    /**
     * Feed charset.
     *
     * @var string
     */
    var $charset;

    /**
     * Constructs a new Jonah_FeedParser parser object.
     */
    function Jonah_FeedParser($charset)
    {
        $this->channel = array();
        $this->image = array();
        $this->item = array();
        $this->story = array();
        $this->charset = $charset;
    }

    /**
     * Initialize the XML parser.
     */
    function init()
    {
        // Check that the charset is supported by the XML parser.
        $allowed_charsets = array('us-ascii', 'iso-8859-1', 'utf-8', 'utf-16');
        if (!in_array($this->charset, $allowed_charsets)) {
            $this->charset = 'utf-8';
        }

        // Create the XML parser.
        $this->parser = xml_parser_create($this->charset);
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_set_element_handler($this->parser, 'startElement', 'endElement');
        xml_set_character_data_handler($this->parser, 'characterData');
        xml_set_default_handler($this->parser, 'defaultHandler');

        // Disable processing instructions and external entities.
        xml_set_processing_instruction_handler($this->parser, '');
        xml_set_external_entity_ref_handler($this->parser, '');
    }

    /**
     * Clean up any existing data - reset to a state where we can
     * cleanly open a new file.
     */
    function cleanup()
    {
        $this->channel = array();
        $this->image = array();
        $this->item = array();
        $this->story = array();
        $this->structure = array();
    }

    /**
     * Actually do the parsing. Separated from the constructor just in
     * case you want to set any other options on the parser, load
     * initial data, whatever.
     *
     * @param $data  The XML feed data to parse.
     */
    function parse($data)
    {
        $this->init();

        // Sanity checks.
        if (!$this->parser) {
            $this->error = 'Could not find xml parser handle';
            return false;
        }

        // Parse.
        if (!@xml_parse($this->parser, $data)) {
            $this->error = sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->parser)), xml_get_current_line_number($this->parser));
            return false;
        }

        // Clean up.
        xml_parser_free($this->parser);

        return true;
    }

    /**
     * Start collecting data about a new element.
     */
    function startElement($parser, $name, $attribs)
    {
        $name = Horde_String::upper($name);
        $attribs = array_change_key_case($attribs, CASE_LOWER);

        switch ($name) {
        case 'FEED':
            $this->format = 'atom';

        case 'CHANNEL':
        case 'IMAGE':
        case 'ITEM':
        case 'ENTRY':
        case 'TEXTINPUT':
            $this->parent = $name;
            break;

        case 'LINUXTODAY':
        case 'UUTISET':
            $this->parent = 'channel';
            break;

        case 'UUTINEN':
            $this->parent = 'item';
            break;

        case 'STORYLIST':
            $this->structure['type'] = 'storylist';
            break;

        case 'RATING':
        case 'DESCRIPTION':
        case 'WIDTH':
        case 'HEIGHT':
        case 'LANGUAGE':
        case 'MANAGINGEDITOR':
        case 'WEBMASTER':
        case 'COPYRIGHT':
        case 'LASTBUILDDATE':
        case 'AUTHOR':
        case 'TOPIC':
        case 'COMMENTS':
            // Userland story format.
        case 'POSTTIME':
        case 'CHANNELTITLE':
        case 'CHANNELURL':
        case 'USERLANDCHANNELID':
        case 'STORYTEXT':
            $this->child = Horde_String::lower($name);
            break;

        case 'LINK':
            $this->child = 'link';
            if ($this->format == 'atom' && !empty($attribs['href'])) {
                if ($this->parent == 'FEED') {
                    $target = &$this->channel;
                } else {
                    $target = &$this->item;
                }

                // For now, make the alternate link, which is most likely to
                // point to the HTML copy of the article, the default one - or,
                // if there isn't yet a link and the rel is empty, use it.
                if ((empty($target['link']) && empty($attribs['rel'])) ||
                    (isset($attribs['rel']) && $attribs['rel'] == 'alternate')) {
                    $target['link'] = $attribs['href'];
                }

                // Store all links named by their rel.
                if (!empty($attribs['rel'])) {
                    $rel = 'link-' . $attribs['rel'];
                    if (isset($target[$rel])) {
                        if (!is_array($target[$rel])) {
                            $target[$rel] = array($target[$rel]);
                        }
                        $target[$rel][] = $attribs['href'];
                    } else {
                        $target[$rel] = $attribs['href'];
                    }
                }
            }
            break;

        case 'NAME':
            if ($this->child != 'author') {
                $this->child = Horde_String::lower($name);
            }
            break;

            // Atom feed entry body.
        case 'CONTENT':
            $this->child = 'body';
            if (isset($attribs['type']) && ($attribs['type'] == 'text/html' || $attribs['type'] == 'html')) {
                $this->item['body_type'] = 'html';
            }
            break;

            // Atom feed entry summary.
        case 'SUMMARY':
            $this->child = 'description';
            break;

            // If we're inside an ITEM, consider this a LINK.
        case 'URL':
            if ($this->parent == 'item') {
                $this->child = 'link';
            } else {
                $this->child = 'url';
            }
            break;

            // For My.Userland channels, let these be STORY tags;
            // otherwise, map them to ITEMs.
        case 'STORY':
            if ($this->parent == 'storylist') {
                $this->parent = 'story';
            } else {
                $this->parent = 'item';
            }
            break;

            // Nonstandard bits that we want to map to standard bits.
        case 'TITLE':
        case 'OTSIKKO':
            $this->child = 'title';
            break;

        case 'PUBLISHED':
        case 'PUBDATE':
        case 'TIME':
        case 'PVM':
            $this->child = 'pubdate';
            break;

        case 'MODIFIED':
        case 'UPDATED':
            $this->child = 'moddate';
            break;

            // More Atom dates.
        case 'CREATED':
        case 'ISSUED':
            $this->child = Horde_String::lower($name);
            break;

        case 'RDF:RDF':
        case 'RSS':
            $this->child = 'junk';
            break;

        // For Yahoo's media namespace extensions
        // see http://search.yahoo.com/mrss
        // Specs say that elements other than content may be
        // either children of media:content OR siblings, so
        // we don't nest these elements.
        case 'MEDIA:CONTENT':
            $this->child = 'media:content';
            foreach ($attribs as $aname => $avalue) {
                $this->item[$this->child][$aname] = $avalue;
            }
            break;
        case 'MEDIA:THUMBNAIL':
            $this->child = 'media:thumbnail';
            foreach ($attribs as $aname => $avalue) {
                $this->item[$this->child][$aname] = $avalue;
            }
            break;
        case 'MEDIA:TITLE':
            $this->child = 'media:title';
            break;
        case 'MEDIA:DESCRIPTION':
            $this->child = 'media:description';
            foreach ($attribs as $aname => $avalue) {
                $this->item[$this->child][$aname] = $avalue;
            }
            // Ensure we have a default
            $this->item[$this->child]['value'] = '';
            break;
        case 'MEDIA:GROUP':
            $this->child='media:group';
            break;
        case 'MEDIA:KEYWORDS':
            $this->child='media:keywords';
            break;

        default:
            if ($this->format == 'atom' && in_array($this->child, array('body', 'description'))) {
                if (!isset($this->item[$this->child])) {
                    $this->item[$this->child] = '';
                }

                $tag = Horde_String::lower($name);
                switch ($tag) {
                case 'br':
                case 'hr':
                    $this->item[$this->child] .= '<' . $tag . '/>';
                    break;

                default:
                    $this->item[$this->child] .= '<' . $tag;
                    foreach ($attribs as $aname => $avalue) {
                        $this->item[$this->child] .= ' ' . $aname . '="' . $avalue . '"';
                    }
                    $this->item[$this->child] .= '>';
                    break;
                }
            } else {
                $this->child = 'junk';
            }
            break;
        }
    }

    /**
     * Handle the ends of XML elements - wrap up whatever we've been
     * putting together and store it for safekeeping.
     */
    function endElement($parser, $name)
    {
        $name = Horde_String::upper($name);
        switch ($name) {
        case 'CHANNEL':
        case 'FEED':
            $this->format = 'atom';
            $this->structure['channel'] = $this->channel;
            break;

        case 'IMAGE':
            $this->structure['image'] = $this->image;
            break;

        case 'STORY':
            if ($this->parent == 'storylist') {
                $this->structure['stories'][] = $this->story;
                $this->story = array();
            } else {
                $this->structure['items'][] = $this->item;
                $this->item = array();
            }
            break;

        case 'TEXTINPUT':
            $this->item['textinput'] = true;
            // No break here; continue to the next case.

        case 'UUTINEN':
        case 'ITEM':
        case 'ENTRY':
            $this->structure['items'][] = $this->item;
            $this->item = array();
            break;
        default:
            if ($this->format == 'atom' && in_array($this->child, array('body', 'description'))) {
                if (!isset($this->item[$this->child])) {
                    $this->item[$this->child] = '';
                }

                $tag = Horde_String::lower($name);
                switch ($tag) {
                case 'br':
                case 'hr':
                    break;

                default:
                    $this->item[$this->child] .= '</' . $tag . '>';
                    break;
                }
            }

        }
    }

    /**
     * The handler for character data encountered in the XML file.
     */
    function characterData($parser, $data)
    {
        if (preg_match('|\S|', $data)) {
            switch ($this->parent) {
            case 'CHANNEL':
            case 'FEED':
                if (!isset($this->channel[$this->child])) {
                    $this->channel[$this->child] = '';
                }
                $this->channel[$this->child] = $data;
                break;

            case 'IMAGE':
                if (!isset($this->image[$this->child])) {
                    $this->image[$this->child] = '';
                }
                $this->image[$this->child] .= $data;
                break;

            case 'STORY':
                if (!isset($this->story[$this->child])) {
                    $this->story[$this->child] = '';
                }
                $this->story[$this->child] .= $data;
                break;

            default:
                switch ($this->child) {
                case 'media:description':
                    $this->item[$this->child]['value'] = $data;
                    break;

                default:

                    if (!isset($this->item[$this->child])) {
                        $this->item[$this->child] = '';
                    }
                    $this->item[$this->child] .= $data;
                    break;
                }
            }
        }
    }

    /**
     * Handles things that we don't recognize. A no-op.
     */
    function defaultHandler($parser, $data)
    {
    }

}
