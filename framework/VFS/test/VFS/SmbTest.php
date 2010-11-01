<?php
/**
 * @category   Horde
 * @package    Horde_VFS
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_VFS
 * @subpackage UnitTests
 */
class VFS_SmbTest extends PHPUnit_Framework_TestCase
{
    public function testParseListing()
    {
        if (!class_exists('Log')) {
            $this->markTestSkipped('The PEAR-Log package is not installed!');
        }

        $vfs = new VFS_smb();

        $listing = $vfs->parseListing(file(dirname(__FILE__) . '/fixtures/samba1.txt'), null, true, false);
        $this->assertType('array', $listing);
        $this->assertEquals(7, count($listing));
        $this->assertEquals(
            array (
                'SystemHiddenReadonlyArchive' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive',
                    'type' => '**dir',
                    'date' => 1243426641,
                    'size' => -1,
                    ),
                'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist',
                    'type' => '**dir',
                    'date' => 1243426451,
                    'size' => -1,
                    ),
                'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt',
                    'type' => 'txt',
                    'date' => 1243426482,
                    'size' => '0',
                    ),
                'Ordner mit Sonderzeichen & ( ) _ - toll' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ordner mit Sonderzeichen & ( ) _ - toll',
                    'type' => '**dir',
                    'date' => 1243426505,
                    'size' => -1,
                    ),
                'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt',
                    'type' => 'txt',
                    'date' => 1243426538,
                    'size' => '0',
                    ),
                'SystemHiddenReadonlyArchive.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txt',
                    'type' => 'txt',
                    'date' => 1243426592,
                    'size' => '0',
                    ),
                'SystemHiddenReadonlyArchive.txte' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txte',
                    'type' => 'txte',
                    'date' => 1243430322,
                    'size' => '31',
                    ),
                ),
            $listing);

        $listing = $vfs->parseListing(file(dirname(__FILE__) . '/fixtures/samba2.txt'), null, true, false);
        $this->assertType('array', $listing);
        $this->assertEquals(26, count($listing));
        $this->assertEquals(
            array (
                'tmp' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tmp',
                    'type' => '**dir',
                    'date' => 1199697783,
                    'size' => -1,
                    ),
                'Der Fischer und seine Frau Märchen.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Der Fischer und seine Frau Märchen.odt',
                    'type' => 'odt',
                    'date' => 1169758536,
                    'size' => '22935',
                    ),
                'Tänze' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Tänze',
                    'type' => '**dir',
                    'date' => 1169756813,
                    'size' => -1,
                    ),
                'Availabilities+rates EE-Dateien' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE-Dateien',
                    'type' => '**dir',
                    'date' => 1126615613,
                    'size' => -1,
                    ),
                'Briefkopf.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Briefkopf.odt',
                    'type' => 'odt',
                    'date' => 1137753731,
                    'size' => '9564',
                    ),
                'Deckblatt.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Deckblatt.pdf',
                    'type' => 'pdf',
                    'date' => 1196284002,
                    'size' => '18027',
                    ),
                'Babymassage.sxw' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Babymassage.sxw',
                    'type' => 'sxw',
                    'date' => 1102376414,
                    'size' => '9228',
                    ),
                'Gutschein.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gutschein.pdf',
                    'type' => 'pdf',
                    'date' => 1168102242,
                    'size' => '10621',
                    ),
                'Die zertanzten Schuh.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Die zertanzten Schuh.pdf',
                    'type' => 'pdf',
                    'date' => 1169483565,
                    'size' => '257955',
                    ),
                'Flyer Im Takt.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Flyer Im Takt.pdf',
                    'type' => 'pdf',
                    'date' => 1169891684,
                    'size' => '42905',
                    ),
                'Availabilities+rates EE.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE.doc',
                    'type' => 'doc',
                    'date' => 1124044046,
                    'size' => '1407488',
                    ),
                'Availabilities+rates EE.htm' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE.htm',
                    'type' => 'htm',
                    'date' => 1126615336,
                    'size' => '262588',
                    ),
                'tt0208m_.ttf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tt0208m_.ttf',
                    'type' => 'ttf',
                    'date' => 1111250096,
                    'size' => '47004',
                    ),
                'Alte Dateien.zip' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Alte Dateien.zip',
                    'type' => 'zip',
                    'date' => 1179697912,
                    'size' => '5566512',
                    ),
                'Availabilities+rates SQ-Dateien' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ-Dateien',
                    'type' => '**dir',
                    'date' => 1126615567,
                    'size' => -1,
                    ),
                'Bobath-Befund.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Bobath-Befund.pdf',
                    'type' => 'pdf',
                    'date' => 1196282600,
                    'size' => '123696',
                    ),
                'Availabilities+rates SQ.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ.doc',
                    'type' => 'doc',
                    'date' => 1124044062,
                    'size' => '109056',
                    ),
                'Availabilities+rates SQ.htm' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ.htm',
                    'type' => 'htm',
                    'date' => 1126615290,
                    'size' => '266079',
                    ),
                'tt0586m_.ttf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tt0586m_.ttf',
                    'type' => 'ttf',
                    'date' => 1111250098,
                    'size' => '35928',
                    ),
                'Gartenkonzept SZOE.html' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.html',
                    'type' => 'html',
                    'date' => 1199698030,
                    'size' => '168801',
                    ),
                '.DS_Store' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => '.DS_Store',
                    'type' => 'ds_store',
                    'date' => 1110391107,
                    'size' => '12292',
                    ),
                'Pfefferkuchenmann.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Pfefferkuchenmann.odt',
                    'type' => 'odt',
                    'date' => 1166644679,
                    'size' => '14399',
                    ),
                'Sockenstrickanleitung mit Bildern.sxw' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Sockenstrickanleitung mit Bildern.sxw',
                    'type' => 'sxw',
                    'date' => 1104172329,
                    'size' => '9518',
                    ),
                'Gartenkonzept SZOE.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.doc',
                    'type' => 'doc',
                    'date' => 1180365752,
                    'size' => '32959488',
                    ),
                'Gartenkonzept SZOE.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.odt',
                    'type' => 'odt',
                    'date' => 1180365528,
                    'size' => '32526103',
                    ),
                'Gartenkonzept SZOE.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.pdf',
                    'type' => 'pdf',
                    'date' => 1179697180,
                    'size' => '32632182',
                    ),
                ),
            $listing);
    }

}
