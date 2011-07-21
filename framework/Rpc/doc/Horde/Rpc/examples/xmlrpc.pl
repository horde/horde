#!/usr/bin/perl -w

die("Please configure the URL, username, and password, and then remove this line.\n");

use XMLRPC::Lite;
use Data::Dumper;

my $proxy = 'http://username:password@example.com/horde/rpc.php';

my $xlite = XMLRPC::Lite
    -> proxy($proxy)
    -> call('calendar.listCalendars');

my $status = $xlite->result;

print Data::Dumper->Dump($status);
