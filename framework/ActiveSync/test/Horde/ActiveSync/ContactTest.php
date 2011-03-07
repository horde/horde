<?php
/*
 * Unit tests for the horde backend
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_ContactTest extends Horde_Test_Case
{
    /**
     * Checks that setting/getting non-existant properties throws an exception.
     */
    public function testNonExistantProperties()
    {
        $contact = new Horde_ActiveSync_Message_Contact();

        $this->setExpectedException('InvalidArgumentException');
        $contact->unknown = 'test';
        $test = $contact->unknown;
    }

    /**
     * Tests that properties that are arrays of values work as expected. Tests
     * the fact that we have to workaround arrays not being returned by
     * reference from __get(), so we can't do ->arrayProperty[] = 'blah' or
     * array_push() in PHP < 5.2.6
     */
    public function testComplexProperties()
    {
        $contact = new Horde_ActiveSync_Message_Contact();
        $this->assertEquals(0, count($contact->children));
        $contact->children[] = 'blah';
        $this->assertEquals(1, count($contact->children));
        $this->assertEquals('blah', array_pop($contact->children));
    }

    /**
     * Test that known properties work as expected.
     */
    public function testKnownPropertiesAreSettable()
    {
        $contact = new Horde_ActiveSync_Message_Contact();

        $contact->anniversary = '1994-03-06';
        $this->assertEquals('1994-03-06', $contact->anniversary);
        
        $contact->assistantname = 'I wish';
        $this->assertEquals('I wish', $contact->assistantname);

        $contact->assistnamephonenumber = '555-555-1234';
        $this->assertEquals('555-555-1234', $contact->assistnamephonenumber);

        $contact->birthday = '1970-03-20';
        $this->assertEquals('1970-03-20', $contact->birthday);

        $contact->body = 'This is the body';
        $this->assertEquals('This is the body', $contact->body);

        $contact->bodysize = 16;
        $this->assertEquals(16, $contact->bodysize);

        $contact->bodytruncated = 'This is';
        $this->assertEquals('This is', $contact->bodytruncated);

        $contact->business2phonenumber = '555-123-4567';
        $this->assertEquals('555-123-4567', $contact->business2phonenumber);

        $contact->businesscity = 'Philadelphia';
        $this->assertEquals('Philadelphia', $contact->businesscity);

        $contact->businesscountry = 'US';
        $this->assertEquals('US', $contact->businesscountry);

        $contact->businesspostalcode = '19148';
        $this->assertEquals('19148', $contact->businesspostalcode);

        $contact->businessstate = 'PA';
        $this->assertEquals('PA', $contact->businessstate);

        $contact->businessstreet = '123 Market St';
        $this->assertEquals('123 Market St', $contact->businessstreet);

        $contact->businessfaxnumber = '555-122-2222';
        $this->assertEquals('555-122-2222', $contact->businessfaxnumber);

        $contact->businessphonenumber = '555-456-4529';
        $this->assertEquals('555-456-4529', $contact->businessphonenumber);

        $contact->carphonenumber = '555-881-7891';
        $this->assertEquals('555-881-7891', $contact->carphonenumber);

        $contact->children = 'Jordyn';
        $this->assertEquals('Jordyn', $contact->children);

        $contact->companyname = 'Horde';
        $this->assertEquals('Horde', $contact->companyname);

        $contact->department = 'QA';
        $this->assertEquals('QA', $contact->department);

        $contact->email1address = 'mike@theupstairsroom.com';
        $this->assertEquals('mike@theupstairsroom.com', $contact->email1address);

        $contact->email2address = 'mrubinsk@horde.org';
        $this->assertEquals('mrubinsk@horde.org', $contact->email2address);

        $contact->email3address = 'mikerubinsky@gmail.com';
        $this->assertEquals('mikerubinsky@gmail.com', $contact->email3address);

        $contact->fileas = 'Michael Rubinsky';
        $this->assertEquals('Michael Rubinsky', $contact->fileas);

        $contact->firstname = 'Michael';
        $this->assertEquals('Michael', $contact->firstname);

        $contact->home2phonenumber = '555-779-1212';
        $this->assertEquals('555-779-1212', $contact->home2phonenumber);

        $contact->homecity = 'Philadelphia';
        $this->assertEquals('Philadelphia', $contact->homecity);

        $contact->homecountry = 'US';
        $this->assertEquals('US', $contact->homecountry);

        $contact->homepostalcode = '19148';
        $this->assertEquals('19148', $contact->homepostalcode);

        $contact->homestate = 'PA';
        $this->assertEquals('PA', $contact->homestate);

        $contact->homestreet = '123 Center St';
        $this->assertEquals('123 Center St', $contact->homestreet);

        $contact->homefaxnumber = '';
        $this->assertEquals('', $contact->homefaxnumber);

        $contact->homephonenumber = '555-789-7897';
        $this->assertEquals('555-789-7897', $contact->homephonenumber);

        $contact->jobtitle = 'developer';
        $this->assertEquals('developer', $contact->jobtitle);

        $contact->lastname = 'Rubinsky';
        $this->assertEquals('Rubinsky', $contact->lastname);

        $contact->middlename = 'Joseph';
        $this->assertEquals('Joseph', $contact->middlename);

        $contact->mobilephonenumber = '555-122-1234';
        $this->assertEquals('555-122-1234', $contact->mobilephonenumber);

        $contact->officelocation = 'Here';
        $this->assertEquals('Here', $contact->officelocation);

        $contact->othercity = 'SomeCity';
        $this->assertEquals('SomeCity', $contact->othercity);

        $contact->othercountry = 'US';
        $this->assertEquals('US', $contact->othercountry);

        $contact->otherpostalcode = '08080';
        $this->assertEquals('08080', $contact->otherpostalcode);

        $contact->otherstate = 'NJ';
        $this->assertEquals('NJ', $contact->otherstate);

        $contact->otherstreet = 'E. Center St';
        $this->assertEquals('E. Center St', $contact->otherstreet);

        $contact->pagernumber = '555-123-1234';
        $this->assertEquals('555-123-1234', $contact->pagernumber);

        $contact->radiophonenumber = '555-123-4567';
        $this->assertEquals('555-123-4567', $contact->radiophonenumber);

        $contact->spouse = 'Ashley';
        $this->assertEquals('Ashley', $contact->spouse);

        $contact->suffix = 'PharmD';
        $this->assertEquals('PharmD', $contact->suffix);

        $contact->title = 'Dr.';
        $this->assertEquals('Dr.', $contact->title);

        $contact->webpage = 'http://theupstairsroom.com';
        $this->assertEquals('http://theupstairsroom.com', $contact->webpage);

        $contact->yomicompanyname = 'TheUpstairsRoom';
        $this->assertEquals('TheUpstairsRoom', $contact->yomicompanyname);

        $contact->yomifirstname = '';
        $this->assertEquals('', $contact->yomifirstname);

        $contact->yomilastname = '';
        $this->assertEquals('', $contact->yomilastname);

        $contact->rtf = 'test';
        $this->assertEquals('test', $contact->rtf);

        $contact->picture = '';
        $this->assertEquals('', $contact->picture);

        $contact->categories = '';
        $this->assertEquals('', $contact->categories);
    }
}
