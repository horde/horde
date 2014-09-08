<?php
/*
 * Unit tests for Horde_ActiveSync_Mime
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_MimeTest extends Horde_Test_Case
{

   public function testHasAttachmentsWithNoAttachment()
   {
        $fixture = file_get_contents(__DIR__ . '/fixtures/email_plain.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        $this->assertEquals(false, $mime->hasAttachments());
        $this->assertEquals(false, $mime->isSigned());
        $this->assertEquals(false, $mime->hasiCalendar());

        $fixture = file_get_contents(__DIR__ . '/fixtures/iOSMultipartAlternative.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        $this->assertEquals(false, $mime->hasAttachments());
        $this->assertEquals(false, $mime->isSigned());
        $this->assertEquals(false, $mime->hasiCalendar());
   }

   public function testSignedNoAttachment()
   {
        $fixture = file_get_contents(__DIR__ . '/fixtures/email_signed.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        $this->assertEquals(false, $mime->hasAttachments());
        $this->assertEquals(true, $mime->isSigned());
        $this->assertEquals(false, $mime->hasiCalendar());
   }

   public function testHasAttachmentsWithAttachment()
   {
        $fixture = file_get_contents(__DIR__ . '/fixtures/signed_attachment.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        $this->assertEquals(true, $mime->hasAttachments());
        $this->assertEquals(true, $mime->isSigned());
        $this->assertEquals(false, $mime->hasiCalendar());
   }

   public function testReplaceMime()
   {
        $fixture = file_get_contents(__DIR__ . '/fixtures/signed_attachment.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        foreach ($mime->contentTypeMap() as $id => $type) {
            if ($mime->isAttachment($id, $type)) {
                $part = new Horde_Mime_Part();
                $part->setType('text/plain');
                $part->setContents(sprintf(
                    _("An attachment named %s was removed by Horde_ActiveSync_Test"),
                    $mime->getPart($id)->getName(true))
                );
                $mime->removePart($id);
                $mime->addPart($part);
            }
        }

        $this->assertEquals(true, $mime->hasAttachments());
        $this->assertEquals('An attachment named foxtrotjobs.png was removed by Horde_ActiveSync_Test', $mime->getPart('3')->getContents());
    }

   public function testHasiCalendar()
   {
        $fixture = file_get_contents(__DIR__ . '/fixtures/invitation_one.eml');
        $mime = new Horde_ActiveSync_Mime(Horde_Mime_Part::parseMessage($fixture));
        $this->assertEquals(true, $mime->hasAttachments());
        $this->assertEquals(false, $mime->isSigned());
        $this->assertEquals(true, (boolean)$mime->hasiCalendar());
   }

}
