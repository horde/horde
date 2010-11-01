<?php
/**
 * @package Horde_Rdo
 */

require_once './Clotho.php';

class XmlItemMapper extends ItemMapper
{
}

class XmlItem extends Item
{
    /**
     * Return an XML representation of this object. The default
     * implementation is unlikely to be useful in most cases and
     * should be overridden by subclasses to be domain-appropriate.
     *
     * @TODO: see http://pear.php.net/pepr/pepr-proposal-show.php?id=361 ?
     *
     * @return string XML representation of $this.
     */
    public function toXml()
    {
        $doc = new DOMDocument('1.0');

        $root = $doc->appendChild($doc->createElement(get_class($this)));
        foreach ($this as $field => $value) {
            $f = $root->appendChild($doc->createElement($field));
            $f->appendChild($doc->createTextNode($value));
        }

        return $doc->saveXML();
    }
}

$im = new XmlItemMapper($conf['adapter']);

$i = $im->findOne(1);
echo $i->toXml();
