#!/usr/bin/perl -w

die("Please configure the URL, username, and password, and then remove this line.\n");

use SOAP::Lite;
use Data::Dumper;

my $proxy = 'http://username:password@example.com/horde/rpc.php';

my $slite = SOAP::Lite
    -> proxy($proxy)
    -> call('calendar.listCalendars');

my $status = $slite->result;

print Data::Dumper->Dump($status);
