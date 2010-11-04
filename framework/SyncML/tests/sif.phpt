--TEST--
SIF tests.
--FILE--
<?php

// Setup stubs.
class BackendStub {
    function logMessage() {}
}
$backend = new BackendStub();

// Load device handler.
require_once dirname(__FILE__) . '/../SyncML/Device.php';
$device = SyncML_Device::factory('Sync4j');

$data = <<<EVENT
BEGIN:VCALENDAR
VERSION:2.0
X-WR-CALNAME:cdillon's Calendar
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20080630T110000Z
DTEND:20080630T120000Z
DTSTAMP:20080630T201939Z
UID:20080630151854.190949aaovgixvhq@www.wolves.k12.mo.us
CREATED:20080630T201854Z
LAST-MODIFIED:20080630T201854Z
SUMMARY:Server02
ORGANIZER;CN=Chris Dillon:mailto:cdillon@wolves.k12.mo.us
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:OPAQUE
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="Dillon,
  Chris":mailto:cdillon@wolves.k12.mo.us
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
EVENT;

echo $device->vevent2sif($data);
echo "\n\n";

$data = <<<EVENT
BEGIN:VCALENDAR
VERSION:2.0
X-WR-CALNAME:cdillon's Calendar
PRODID:-//The Horde Project//Horde_iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART;VALUE=DATE:20080630
DTEND;VALUE=DATE:20080631
DTSTAMP:20080630T201939Z
UID:20080630151854.190949aaovgixvhq@www.wolves.k12.mo.us
CREATED:20080630T201854Z
LAST-MODIFIED:20080630T201854Z
SUMMARY:Server02
ORGANIZER;CN=Chris Dillon:mailto:cdillon@wolves.k12.mo.us
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:OPAQUE
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="Dillon,
  Chris":mailto:cdillon@wolves.k12.mo.us
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
EVENT;

echo $device->vevent2sif($data);
echo "\n\n";

$data = <<<EVENT
BEGIN:VCALENDAR
VERSION:2.0
X-WR-CALNAME:Agenda de Pruebas
PRODID:-//The Horde Project//Horde_iCalendar Library\, Horde 3.3.8//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20101101T090000Z
DTEND:20101101T100000Z
DTSTAMP:20101025T104946Z
UID:20101025124222.12755wqg94msihvy@example.com
CREATED:20101025T104222Z
LAST-MODIFIED:20101025T104846Z
SUMMARY:Cinco-Lunes
ORGANIZER;CN=Pruebas:mailto:pruebas@example.com
CATEGORIES:Trabajo
LOCATION:Korta
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:OPAQUE
RRULE:FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20101130T225959Z
EXDATE:20101108T090000Z
EXDATE:20101122T090000Z
EXDATE:20101129T090000Z
EXDATE:20101115T090000Z
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
EVENT;

echo $device->vevent2sif($data);
echo "\n\n";

$data = <<<CONTACT
<?xml version="1.0" encoding="UTF-8"?>
<contact>
<Anniversary/>
<AssistantName/>
<AssistantTelephoneNumber/>
<BillingInformation/>
<Birthday>2008-10-18</Birthday>
<Body>Comments
More comments
And just a couple more</Body>
<Business2TelephoneNumber/>
<BusinessAddressCity>Golden Hills</BusinessAddressCity>
<BusinessAddressCountry>Australia</BusinessAddressCountry>
<BusinessAddressPostOfficeBox/>
<BusinessAddressPostalCode>4009</BusinessAddressPostalCode>
<BusinessAddressState>Qld</BusinessAddressState>
<BusinessAddressStreet>Company
Unit 2, 123 St Freds Tce</BusinessAddressStreet>
<BusinessFaxNumber/>
<BusinessTelephoneNumber>+61 712341234</BusinessTelephoneNumber>
<CallbackTelephoneNumber/>
<CarTelephoneNumber/>
<Categories/>
<Children/>
<Companies/>
<CompanyMainTelephoneNumber/>
<CompanyName>Company</CompanyName>
<ComputerNetworkName/>
<Department/>
<Email1Address>test@domain.com</Email1Address>
<Email1AddressType>SMTP</Email1AddressType>
<Email2Address>user@seconddomain.com</Email2Address>
<Email2AddressType>SMTP</Email2AddressType>
<Email3Address/>
<Email3AddressType/>
<FileAs>Lastname, Firstname</FileAs>
<FirstName>Firstname</FirstName>
<Folder>DEFAULT_FOLDER</Folder>
<Gender>0</Gender>
<Hobby/>
<Home2TelephoneNumber/>
<HomeAddressCity/>
<HomeAddressCountry/>
<HomeAddressPostOfficeBox/>
<HomeAddressPostalCode/>
<HomeAddressState/>
<HomeAddressStreet/>
<HomeFaxNumber/>
<HomeTelephoneNumber/>
<HomeWebPage/>
<IMAddress/>
<Importance>1</Importance>
<Initials>F.L.</Initials>
<JobTitle/>
<Language/>
<LastName>Lastname</LastName>
<MailingAddress>Company
Unit 2, 123 St Freds Tce
Golden Hills  Qld  4009
Australia</MailingAddress>
<ManagerName/>
<MiddleName/>
<Mileage/>
<MobileTelephoneNumber>+61 123123123</MobileTelephoneNumber>
<NickName/>
<OfficeLocation/>
<OrganizationalIDNumber/>
<OtherAddressCity/>
<OtherAddressCountry/>
<OtherAddressPostOfficeBox/>
<OtherAddressPostalCode/>
<OtherAddressState/>
<OtherAddressStreet/>
<OtherFaxNumber/>
<OtherTelephoneNumber/>
<PagerNumber/>
<Photo/>
<PrimaryTelephoneNumber/>
<Profession/>
<RadioTelephoneNumber/>
<Sensitivity>0</Sensitivity>
<Spouse/>
<Subject>Firstname Lastname</Subject>
<Suffix/>
<TelexNumber/>
<Title/>
<WebPage/>
<YomiCompanyName/>
<YomiFirstName/>
<YomiLastName/>
</contact>
CONTACT;

echo $device->sif2vcard($data);

?>
--EXPECT--
<?xml version="1.0"?><appointment><ReminderSet>1</ReminderSet><IsRecurring>0</IsRecurring><BusyStatus>2</BusyStatus><AllDayEvent>0</AllDayEvent><Start>20080630T110000Z</Start><End>20080630T120000Z</End><Subject>Server02</Subject><Sensitivity>0</Sensitivity><ReminderMinutesBeforeStart>15</ReminderMinutesBeforeStart><Duration>60</Duration></appointment>

<?xml version="1.0"?><appointment><ReminderSet>1</ReminderSet><IsRecurring>0</IsRecurring><BusyStatus>2</BusyStatus><AllDayEvent>1</AllDayEvent><Start>2008-06-30</Start><End>2008-06-30</End><Subject>Server02</Subject><Sensitivity>0</Sensitivity><ReminderMinutesBeforeStart>15</ReminderMinutesBeforeStart></appointment>

<?xml version="1.0"?><appointment><ReminderSet>1</ReminderSet><IsRecurring>1</IsRecurring><BusyStatus>2</BusyStatus><AllDayEvent>0</AllDayEvent><Start>20101101T090000Z</Start><End>20101101T100000Z</End><Subject>Cinco-Lunes</Subject><Categories>Trabajo</Categories><Location>Korta</Location><Sensitivity>0</Sensitivity><Interval>1</Interval><RecurrenceType>1</RecurrenceType><DayOfWeekMask>2</DayOfWeekMask><NoEndDate>0</NoEndDate><PatternEndDate>20101130T225959Z</PatternEndDate><ReminderMinutesBeforeStart>15</ReminderMinutesBeforeStart><Duration>60</Duration></appointment>

BEGIN:VCARD
VERSION:3.0
FN:Lastname\, Firstname
TEL;TYPE=WORK:+61 712341234
TEL;TYPE=CELL:+61 123123123
EMAIL:test@domain.com
EMAIL;TYPE=HOME:user@seconddomain.com
NOTE:Comments\nMore comments\nAnd just a couple more
BDAY:2008-10-18
N:Lastname;Firstname;;;
ADR;TYPE=WORK:;;Company\nUnit 2\, 123 St Freds Tce;Golden
  Hills;Qld;4009;Australia
ORG:Company
END:VCARD
