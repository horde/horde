<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */

class Horde_Util_CsvTest extends PHPUnit_Framework_TestCase
{
    protected function readCsv($file, $conf = array())
    {
        $fp = fopen(dirname(__FILE__) . '/fixtures/' . $file, 'r');
        $data = array();
        while ($res = Horde_Util::getCsv($fp, $conf)) {
            $data[] = $res;
        }
        return $data;
    }

    public function test001()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Field 1-1',
                                          1 => 'Field 1-2',
                                          2 => 'Field 1-3',
                                          3 => 'Field 1-4',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Field 2-1',
                                          1 => 'Field 2-2',
                                          2 => 'Field 2-3',
                                          3 => '',
                                          ),
                                   2 =>
                                   array (
                                          0 => 'Field 3-1',
                                          1 => 'Field 3-2',
                                          2 => '',
                                          3 => '',
                                          ),
                                   3 =>
                                   array (
                                          0 => 'Field 4-1',
                                          1 => '',
                                          2 => '',
                                          3 => '',
                                          ),
                                   ),
                            $this->readCsv('001.csv', array('length' => 4)));
    }

    public function test002()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Field 1-1',
                                          1 => 'Field 1-2',
                                          2 => 'Field 1-3',
                                          3 => 'Field 1-4',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Field 2-1',
                                          1 => 'Field 2-2',
                                          2 => 'Field 2-3',
                                          3 => 'Field 2-4',
                                          ),
                                   2 =>
                                   array (
                                          0 => 'Field 3-1',
                                          1 => 'Field 3-2',
                                          2 => '',
                                          3 => '',
                                          ),
                                   3 =>
                                   array (
                                          0 => 'Field 4-1',
                                          1 => '',
                                          2 => '',
                                          3 => '',
                                          ),
                                   ),
                            $this->readCsv('002.csv', array('length' => 4)));
    }

    public function test003()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Field 1-1',
                                          1 => 'Field 1-2',
                                          2 => 'Field 1-3',
                                          3 => 'Field 1-4',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Field 2-1',
                                          1 => 'Field 2-2',
                                          2 => 'Field 2-3',
                                          3 => 'I\'m multiline
Field',
                                          ),
                                   2 =>
                                   array (
                                          0 => 'Field 3-1',
                                          1 => 'Field 3-2',
                                          2 => 'Field 3-3',
                                          3 => '',
                                          ),
                                   ),
                            $this->readCsv('003.csv', array('length' => 4)));
    }

    public function test004()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Field 1-1',
                                          1 => 'Field 1-2',
                                          2 => 'Field 1-3',
                                          3 => 'Field 1-4',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Field 2-1',
                                          1 => 'Field 2-2',
                                          2 => 'Field 2-3',
                                          3 => 'I\'m multiline
Field',
                                          ),
                                   2 =>
                                   array (
                                          0 => 'Field 3-1',
                                          1 => 'Field 3-2',
                                          2 => 'Field 3-3',
                                          3 => '',
                                          ),
                                   ),
                            $this->readCsv('004.csv', array('length' => 4)));
    }

    public function testBug3839()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Subject',
                                          1 => 'Start Date',
                                          2 => 'Start Time',
                                          3 => 'End Date',
                                          4 => 'End Time',
                                          5 => 'All day event',
                                          6 => 'Reminder on/off',
                                          7 => 'Reminder Date',
                                          8 => 'Reminder Time',
                                          9 => 'Category',
                                          10 => 'Description',
                                          11 => 'Priority',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Inservice on new resource: "CPNP Toolkit"',
                                          1 => '2004-11-08',
                                          2 => '10:30 AM',
                                          3 => '2004-11-08',
                                          4 => '11:30 AM',
                                          5 => 'FALSE',
                                          6 => 'FALSE',
                                          7 => '',
                                          8 => '',
                                          9 => 'Training',
                                          10 => 'CPN Program ...
Inservice on new resource: "CPNP Toolkit"

<b>Registration Deadline:  October 27, 2004, noon</b>

<a href="F041108A-Eval.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A-Eval.pdf" target="_blank">  Session Evaluation - Eligibility for Prize!</a>

<a href="F041108A-DI.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A-DI.pdf" target="_blank">  Dial In Numbers for Sites Registered</a>

<a href="F041108A.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A.pdf" target="_blank">  Poster and Registration Form</a>

Facilitator:  Manager

preblurb preblurb preblurb preblurb preblurb preblurb preblurb preblurb preblurb  "CPNP Toolkit".  postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb .

postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb

Come check out the new resource!',
                                          11 => 'Normal',
                                          ),
                                   ),
                            $this->readCsv('bug_3839.csv', array('length' => 12, 'separator' => '~')));
    }

    public function testBug4025()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Betreff',
                                          1 => 'Beginnt am',
                                          2 => 'Beginnt um',
                                          3 => 'Endet am',
                                          4 => 'Endet um',
                                          5 => 'Ganztägiges Ereignis',
                                          6 => 'Erinnerung Ein/Aus',
                                          7 => 'Erinnerung am',
                                          8 => 'Erinnerung um',
                                          9 => 'Besprechungsplanung',
                                          10 => 'Erforderliche Teilnehmer',
                                          11 => 'Optionale Teilnehmer',
                                          12 => 'Besprechungsressourcen',
                                          13 => 'Abrechnungsinformationen',
                                          14 => 'Beschreibung',
                                          15 => 'Kategorien',
                                          16 => 'Ort',
                                          17 => 'Priorität',
                                          18 => 'Privat',
                                          19 => 'Reisekilometer',
                                          20 => 'Vertraulichkeit',
                                          21 => 'Zeitspanne zeigen als',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'Burger Download Session',
                                          1 => '2.5.2006',
                                          2 => '11:50:00',
                                          3 => '2.5.2006',
                                          4 => '13:00:00',
                                          5 => 'Aus',
                                          6 => 'Ein',
                                          7 => '2.5.2006',
                                          8 => '11:35:00',
                                          9 => 'Haas, Jörg',
                                          10 => 'Kuhl, Oliver',
                                          11 => '',
                                          12 => '',
                                          13 => '',
                                          14 => '
',
                                          15 => '',
                                          16 => 'Burger Upload Station (Burger King)',
                                          17 => 'Normal',
                                          18 => 'Aus',
                                          19 => '',
                                          20 => 'Normal',
                                          21 => '1',
                                          ),
                                   ),
                            $this->readCsv('bug_4025.csv', array('length' => 22)));
    }

    public function testBug6311()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Title',
                                          1 => 'First Name',
                                          2 => 'Middle Name',
                                          3 => 'Last Name',
                                          4 => 'Suffix',
                                          5 => 'Company',
                                          6 => 'Department',
                                          7 => 'Job Title',
                                          8 => 'Business Street',
                                          9 => 'Business Street 2',
                                          10 => 'Business Street 3',
                                          11 => 'Business City',
                                          12 => 'Business State',
                                          13 => 'Business Postal Code',
                                          14 => 'Business Country/Region',
                                          15 => 'Home Street',
                                          16 => 'Home Street 2',
                                          17 => 'Home Street 3',
                                          18 => 'Home City',
                                          19 => 'Home State',
                                          20 => 'Home Postal Code',
                                          21 => 'Home Country/Region',
                                          22 => 'Other Street',
                                          23 => 'Other Street 2',
                                          24 => 'Other Street 3',
                                          25 => 'Other City',
                                          26 => 'Other State',
                                          27 => 'Other Postal Code',
                                          28 => 'Other Country/Region',
                                          29 => 'Assistant\'s Phone',
                                          30 => 'Business Fax',
                                          31 => 'Business Phone',
                                          32 => 'Business Phone 2',
                                          33 => 'Callback',
                                          34 => 'Car Phone',
                                          35 => 'Company Main Phone',
                                          36 => 'Home Fax',
                                          37 => 'Home Phone',
                                          38 => 'Home Phone 2',
                                          39 => 'ISDN',
                                          40 => 'Mobile Phone',
                                          41 => 'Other Fax',
                                          42 => 'Other Phone',
                                          43 => 'Pager',
                                          44 => 'Primary Phone',
                                          45 => 'Radio Phone',
                                          46 => 'TTY/TDD Phone',
                                          47 => 'Telex',
                                          48 => 'Account',
                                          49 => 'Anniversary',
                                          50 => 'Assistant\'s Name',
                                          51 => 'Billing Information',
                                          52 => 'Birthday',
                                          53 => 'Business Address PO Box',
                                          54 => 'Categories',
                                          55 => 'Children',
                                          56 => 'Directory Server',
                                          57 => 'E-mail Address',
                                          58 => 'E-mail Type',
                                          59 => 'E-mail Display Name',
                                          60 => 'E-mail 2 Address',
                                          61 => 'E-mail 2 Type',
                                          62 => 'E-mail 2 Display Name',
                                          63 => 'E-mail 3 Address',
                                          64 => 'E-mail 3 Type',
                                          65 => 'E-mail 3 Display Name',
                                          66 => 'Gender',
                                          67 => 'Government ID Number',
                                          68 => 'Hobby',
                                          69 => 'Home Address PO Box',
                                          70 => 'Initials',
                                          71 => 'Internet Free Busy',
                                          72 => 'Keywords',
                                          73 => 'Language',
                                          74 => 'Location',
                                          75 => 'Manager\'s Name',
                                          76 => 'Mileage',
                                          77 => 'Notes',
                                          78 => 'Office Location',
                                          79 => 'Organizational ID Number',
                                          80 => 'Other Address PO Box',
                                          81 => 'Priority',
                                          82 => 'Private',
                                          83 => 'Profession',
                                          84 => 'Referred By',
                                          85 => 'Sensitivity',
                                          86 => 'Spouse',
                                          87 => 'User 1',
                                          88 => 'User 2',
                                          89 => 'User 3',
                                          90 => 'User 4',
                                          91 => 'Web Page',
                                          ),
                                   1 =>
                                   array (
                                          0 => '',
                                          1 => 'John',
                                          2 => '',
                                          3 => 'Smith',
                                          4 => '',
                                          5 => 'International Inc',
                                          6 => '',
                                          7 => '',
                                          8 => '',
                                          9 => '',
                                          10 => '',
                                          11 => '',
                                          12 => '',
                                          13 => '',
                                          14 => '',
                                          15 => '',
                                          16 => '',
                                          17 => '',
                                          18 => '',
                                          19 => '',
                                          20 => '',
                                          21 => '',
                                          22 => '',
                                          23 => '',
                                          24 => '',
                                          25 => '',
                                          26 => '',
                                          27 => '',
                                          28 => '',
                                          29 => '',
                                          30 => '(123) 555-1111',
                                          31 => '(123) 555-2222',
                                          32 => '',
                                          33 => '',
                                          34 => '',
                                          35 => '',
                                          36 => '',
                                          37 => '',
                                          38 => '',
                                          39 => '',
                                          40 => '(123) 555-3333',
                                          41 => '',
                                          42 => '',
                                          43 => '',
                                          44 => '',
                                          45 => '',
                                          46 => '',
                                          47 => '',
                                          48 => '',
                                          49 => '0/0/00',
                                          50 => '',
                                          51 => '',
                                          52 => '0/0/00',
                                          53 => '',
                                          54 => 'Programming',
                                          55 => '',
                                          56 => '',
                                          57 => 'john@example.com',
                                          58 => 'SMTP',
                                          59 => 'John Smith (john@example.com)',
                                          60 => '',
                                          61 => '',
                                          62 => '',
                                          63 => '',
                                          64 => '',
                                          65 => '',
                                          66 => 'Unspecified',
                                          67 => '',
                                          68 => '',
                                          69 => '',
                                          70 => 'J.S.',
                                          71 => '',
                                          72 => '',
                                          73 => '',
                                          74 => '',
                                          75 => '',
                                          76 => '',
                                          77 => 'PHP
Perl
Python
',
                                          78 => '',
                                          79 => '',
                                          80 => '',
                                          81 => 'Normal',
                                          82 => 'False',
                                          83 => '',
                                          84 => '',
                                          85 => 'Normal',
                                          86 => '',
                                          87 => '',
                                          88 => '',
                                          89 => '',
                                          90 => '',
                                          91 => '',
                                          ),
                                   ),
                            $this->readCsv('bug_6311.csv', array('length' => 92)));
    }

    public function testBug6370()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Title',
                                          1 => 'First Name',
                                          2 => 'Middle Name',
                                          3 => 'Last Name',
                                          4 => 'Suffix',
                                          5 => 'Company',
                                          6 => 'Department',
                                          7 => 'Job Title',
                                          8 => 'Business Street',
                                          9 => 'Business Street 2',
                                          10 => 'Business Street 3',
                                          11 => 'Business City',
                                          12 => 'Business State',
                                          13 => 'Business Postal Code',
                                          14 => 'Business Country/Region',
                                          15 => 'Home Street',
                                          16 => 'Home Street 2',
                                          17 => 'Home Street 3',
                                          18 => 'Home City',
                                          19 => 'Home State',
                                          20 => 'Home Postal Code',
                                          21 => 'Home Country/Region',
                                          22 => 'Other Street',
                                          23 => 'Other Street 2',
                                          24 => 'Other Street 3',
                                          25 => 'Other City',
                                          26 => 'Other State',
                                          27 => 'Other Postal Code',
                                          28 => 'Other Country/Region',
                                          29 => 'Assistant\'s Phone',
                                          30 => 'Business Fax',
                                          31 => 'Business Phone',
                                          32 => 'Business Phone 2',
                                          33 => 'Callback',
                                          34 => 'Car Phone',
                                          35 => 'Company Main Phone',
                                          36 => 'Home Fax',
                                          37 => 'Home Phone',
                                          38 => 'Home Phone 2',
                                          39 => 'ISDN',
                                          40 => 'Mobile Phone',
                                          41 => 'Other Fax',
                                          42 => 'Other Phone',
                                          43 => 'Pager',
                                          44 => 'Primary Phone',
                                          45 => 'Radio Phone',
                                          46 => 'TTY/TDD Phone',
                                          47 => 'Telex',
                                          48 => 'Account',
                                          49 => 'Anniversary',
                                          50 => 'Assistant\'s Name',
                                          51 => 'Billing Information',
                                          52 => 'Birthday',
                                          53 => 'Business Address PO Box',
                                          54 => 'Categories',
                                          55 => 'Children',
                                          56 => 'Directory Server',
                                          57 => 'E-mail Address',
                                          58 => 'E-mail Type',
                                          59 => 'E-mail Display Name',
                                          60 => 'E-mail 2 Address',
                                          61 => 'E-mail 2 Type',
                                          62 => 'E-mail 2 Display Name',
                                          63 => 'E-mail 3 Address',
                                          64 => 'E-mail 3 Type',
                                          65 => 'E-mail 3 Display Name',
                                          66 => 'Gender',
                                          67 => 'Government ID Number',
                                          68 => 'Hobby',
                                          69 => 'Home Address PO Box',
                                          70 => 'Initials',
                                          71 => 'Internet Free Busy',
                                          72 => 'Keywords',
                                          73 => 'Language',
                                          74 => 'Location',
                                          75 => 'Manager\'s Name',
                                          76 => 'Mileage',
                                          77 => 'Notes',
                                          78 => 'Office Location',
                                          79 => 'Organizational ID Number',
                                          80 => 'Other Address PO Box',
                                          81 => 'Priority',
                                          82 => 'Private',
                                          83 => 'Profession',
                                          84 => 'Referred By',
                                          85 => 'Sensitivity',
                                          86 => 'Spouse',
                                          87 => 'User 1',
                                          88 => 'User 2',
                                          89 => 'User 3',
                                          90 => 'User 4',
                                          91 => 'Web Page',
                                          ),
                                   1 =>
                                   array (
                                          0 => '',
                                          1 => '',
                                          2 => '',
                                          3 => '',
                                          4 => '',
                                          5 => '',
                                          6 => '',
                                          7 => '',
                                          8 => 'Big Tower\'", 1" Floor
123 Main Street',
                                          9 => '',
                                          10 => '',
                                          11 => '',
                                          12 => '',
                                          13 => '',
                                          14 => '',
                                          15 => '',
                                          16 => '',
                                          17 => '',
                                          18 => '',
                                          19 => '',
                                          20 => '',
                                          21 => '',
                                          22 => '',
                                          23 => '',
                                          24 => '',
                                          25 => '',
                                          26 => '',
                                          27 => '',
                                          28 => '',
                                          29 => '',
                                          30 => '',
                                          31 => '',
                                          32 => '',
                                          33 => '',
                                          34 => '',
                                          35 => '',
                                          36 => '',
                                          37 => '',
                                          38 => '',
                                          39 => '',
                                          40 => '',
                                          41 => '',
                                          42 => '',
                                          43 => '',
                                          44 => '',
                                          45 => '',
                                          46 => '',
                                          47 => '',
                                          48 => '',
                                          49 => '0/0/00',
                                          50 => '',
                                          51 => '',
                                          52 => '0/0/00',
                                          53 => '',
                                          54 => '',
                                          55 => '',
                                          56 => '',
                                          57 => '',
                                          58 => '',
                                          59 => '',
                                          60 => '',
                                          61 => '',
                                          62 => '',
                                          63 => '',
                                          64 => '',
                                          65 => '',
                                          66 => 'Unspecified',
                                          67 => '',
                                          68 => '',
                                          69 => '',
                                          70 => '',
                                          71 => '',
                                          72 => '',
                                          73 => '',
                                          74 => '',
                                          75 => '',
                                          76 => '',
                                          77 => '',
                                          78 => '',
                                          79 => '',
                                          80 => '',
                                          81 => 'Normal',
                                          82 => 'False',
                                          83 => '',
                                          84 => '',
                                          85 => 'Normal',
                                          86 => '',
                                          87 => '',
                                          88 => '',
                                          89 => '',
                                          90 => '',
                                          91 => '',
                                          ),
                                   ),
                            $this->readCsv('bug_6370.csv', array('length' => 92)));
    }

    public function testBug6372()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'Title',
                                          1 => 'First Name',
                                          2 => 'Middle Name',
                                          3 => 'Last Name',
                                          4 => 'Suffix',
                                          5 => 'Company',
                                          6 => 'Department',
                                          7 => 'Job Title',
                                          8 => 'Business Street',
                                          9 => 'Business Street 2',
                                          10 => 'Business Street 3',
                                          11 => 'Business City',
                                          12 => 'Business State',
                                          13 => 'Business Postal Code',
                                          14 => 'Business Country/Region',
                                          15 => 'Home Street',
                                          16 => 'Home Street 2',
                                          17 => 'Home Street 3',
                                          18 => 'Home City',
                                          19 => 'Home State',
                                          20 => 'Home Postal Code',
                                          21 => 'Home Country/Region',
                                          22 => 'Other Street',
                                          23 => 'Other Street 2',
                                          24 => 'Other Street 3',
                                          25 => 'Other City',
                                          26 => 'Other State',
                                          27 => 'Other Postal Code',
                                          28 => 'Other Country/Region',
                                          29 => 'Assistant\'s Phone',
                                          30 => 'Business Fax',
                                          31 => 'Business Phone',
                                          32 => 'Business Phone 2',
                                          33 => 'Callback',
                                          34 => 'Car Phone',
                                          35 => 'Company Main Phone',
                                          36 => 'Home Fax',
                                          37 => 'Home Phone',
                                          38 => 'Home Phone 2',
                                          39 => 'ISDN',
                                          40 => 'Mobile Phone',
                                          41 => 'Other Fax',
                                          42 => 'Other Phone',
                                          43 => 'Pager',
                                          44 => 'Primary Phone',
                                          45 => 'Radio Phone',
                                          46 => 'TTY/TDD Phone',
                                          47 => 'Telex',
                                          48 => 'Account',
                                          49 => 'Anniversary',
                                          50 => 'Assistant\'s Name',
                                          51 => 'Billing Information',
                                          52 => 'Birthday',
                                          53 => 'Business Address PO Box',
                                          54 => 'Categories',
                                          55 => 'Children',
                                          56 => 'Directory Server',
                                          57 => 'E-mail Address',
                                          58 => 'E-mail Type',
                                          59 => 'E-mail Display Name',
                                          60 => 'E-mail 2 Address',
                                          61 => 'E-mail 2 Type',
                                          62 => 'E-mail 2 Display Name',
                                          63 => 'E-mail 3 Address',
                                          64 => 'E-mail 3 Type',
                                          65 => 'E-mail 3 Display Name',
                                          66 => 'Gender',
                                          67 => 'Government ID Number',
                                          68 => 'Hobby',
                                          69 => 'Home Address PO Box',
                                          70 => 'Initials',
                                          71 => 'Internet Free Busy',
                                          72 => 'Keywords',
                                          73 => 'Language',
                                          74 => 'Location',
                                          75 => 'Manager\'s Name',
                                          76 => 'Mileage',
                                          77 => 'Notes',
                                          78 => 'Office Location',
                                          79 => 'Organizational ID Number',
                                          80 => 'Other Address PO Box',
                                          81 => 'Priority',
                                          82 => 'Private',
                                          83 => 'Profession',
                                          84 => 'Referred By',
                                          85 => 'Sensitivity',
                                          86 => 'Spouse',
                                          87 => 'User 1',
                                          88 => 'User 2',
                                          89 => 'User 3',
                                          90 => 'User 4',
                                          91 => 'Web Page',
                                          ),
                                   1 =>
                                   array (
                                          0 => '',
                                          1 => '',
                                          2 => '',
                                          3 => '',
                                          4 => '',
                                          5 => '',
                                          6 => '',
                                          7 => '',
                                          8 => '123, 12th Floor,
Main Street',
                                          9 => '',
                                          10 => '',
                                          11 => '',
                                          12 => '',
                                          13 => '',
                                          14 => '',
                                          15 => '',
                                          16 => '',
                                          17 => '',
                                          18 => '',
                                          19 => '',
                                          20 => '',
                                          21 => '',
                                          22 => '',
                                          23 => '',
                                          24 => '',
                                          25 => '',
                                          26 => '',
                                          27 => '',
                                          28 => '',
                                          29 => '',
                                          30 => '',
                                          31 => '',
                                          32 => '',
                                          33 => '',
                                          34 => '',
                                          35 => '',
                                          36 => '',
                                          37 => '',
                                          38 => '',
                                          39 => '',
                                          40 => '',
                                          41 => '',
                                          42 => '',
                                          43 => '',
                                          44 => '',
                                          45 => '',
                                          46 => '',
                                          47 => '',
                                          48 => '',
                                          49 => '0/0/00',
                                          50 => '',
                                          51 => '',
                                          52 => '0/0/00',
                                          53 => '',
                                          54 => '',
                                          55 => '',
                                          56 => '',
                                          57 => '',
                                          58 => '',
                                          59 => '',
                                          60 => '',
                                          61 => '',
                                          62 => '',
                                          63 => '',
                                          64 => '',
                                          65 => '',
                                          66 => 'Unspecified',
                                          67 => '',
                                          68 => '',
                                          69 => '',
                                          70 => '',
                                          71 => '',
                                          72 => '',
                                          73 => '',
                                          74 => '',
                                          75 => '',
                                          76 => '',
                                          77 => '
',
                                          78 => '',
                                          79 => '',
                                          80 => '',
                                          81 => 'Normal',
                                          82 => 'False',
                                          83 => '',
                                          84 => '',
                                          85 => 'Normal',
                                          86 => '',
                                          87 => '',
                                          88 => '',
                                          89 => '',
                                          90 => '',
                                          91 => '',
                                          ),
                                   ),
                            $this->readCsv('bug_6372.csv', array('length' => 92)));
    }

    public function testLineEndings()
    {
        foreach (array('simple_lf', 'simple_crlf', 'notrailing_lf', 'notrailing_crlf') as $test) {
            $this->assertEquals(array (
                                       0 =>
                                       array (
                                              0 => 'one',
                                              1 => 'two',
                                              2 => 'three',
                                              ),
                                       1 =>
                                       array (
                                              0 => 'four',
                                              1 => 'five',
                                              2 => 'six',
                                              ),
                                       ),
                                $this->readCsv($test . '.csv'));
        }
    }

    public function testMultiLine()
    {
        $this->assertEquals(array (
                                   0 =>
                                   array (
                                          0 => 'one',
                                          1 => 'two',
                                          2 => 'three
four',
                                          ),
                                   1 =>
                                   array (
                                          0 => 'five',
                                          1 => 'six
seven',
                                          2 => 'eight',
                                          ),
                                   2 =>
                                   array (
                                          0 => 'nine',
                                          1 => 'ten',
                                          2 => 'eleven
twelve',
                                          ),
                                   3 =>
                                   array (
                                          0 => 'one',
                                          1 => 'two',
                                          2 => 'three
 four',
                                          ),
                                   ),
                            $this->readCsv('multiline1.csv'));
    }

    public function testQuotes()
    {
        for ($i = 1; $i <= 2; $i++) {
            $this->assertEquals(array (
                                       0 =>
                                       array (
                                              0 => 'one',
                                              1 => 'two',
                                              2 => 'three',
                                              ),
                                       1 =>
                                       array (
                                              0 => 'four',
                                              1 => 'five six',
                                              2 => 'seven',
                                              ),
                                       ),
                                $this->readCsv('quote' . $i . '.csv'));
        }

        for ($i = 3; $i <= 5; $i++) {
            $this->assertEquals(array (
                                       0 =>
                                       array (
                                              0 => 'one two',
                                              1 => 'three, four',
                                              2 => 'five',
                                              ),
                                       1 =>
                                       array (
                                              0 => 'six',
                                              1 => 'seven ',
                                              2 => 'eight',
                                              ),
                                       ),
                                $this->readCsv('quote' . $i . '.csv'));
        }
    }
}
