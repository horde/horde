<?php
/*
 * Unit tests for Horde_ActiveSync_Folder_Imap
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_ImapFolderTest extends Horde_Test_Case
{
    public function testInitialState()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('INBOX', Horde_ActiveSync::CLASS_EMAIL);
        $thrown = false;
        try {
            $folder->checkValidity(array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => '123'));
        } catch (Horde_ActiveSync_Exception $e) {
            $thrown = true;
        }
        $this->assertEquals(true, $thrown);
        $this->assertEquals(0, $folder->uidnext());
        $this->assertEquals(0, $folder->modseq());
        $this->assertEquals(array(), $folder->messages());
        $this->assertEquals(array(), $folder->flags());
        $this->assertEquals(array(), $folder->added());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals(0, $folder->minuid());
    }

    public function testNoModseqUpdate()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('INBOX', Horde_ActiveSync::CLASS_EMAIL);
        $status = array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => 100, Horde_ActiveSync_Folder_Imap::UIDNEXT => 105);

        // Initial state for nonmodseq
        $msg_changes = array(100, 101, 102, 103, 104);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 0),
            101 => array('read' => 0, 'flagged' => 0),
            102 => array('read' => 0, 'flagged' => 0),
            103 => array('read' => 0, 'flagged' => 0),
            104 => array('read' => 0, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);

        $this->assertEquals($msg_changes, $folder->added());
        $this->assertEquals($flag_changes, $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals(array(), $folder->messages());


        $folder->setStatus($status);
        $folder->updateState();

        $this->assertEquals(array(), $folder->added());
        $this->assertEquals(array(), $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals($msg_changes, $folder->messages());

        // Now simulate some flag changes and new messages.
        $msg_changes = array(100, 101, 102, 103, 104, 105);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 1),
            101 => array('read' => 0, 'flagged' => 0),
            102 => array('read' => 0, 'flagged' => 0),
            103 => array('read' => 0, 'flagged' => 0),
            104 => array('read' => 0, 'flagged' => 0),
            105 => array('read' => 1, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);
        $this->assertEquals(array(105), $folder->added());
        $this->assertEquals(array(100), $folder->changed());

        $status[Horde_ActiveSync_Folder_Imap::UIDNEXT] = 106;
        $folder->setStatus($status);
        $folder->updateState();
    }

    public function testModseqUpdate()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('INBOX', Horde_ActiveSync::CLASS_EMAIL);
        $status = array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => 100, Horde_ActiveSync_Folder_Imap::UIDNEXT => 105, Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ => 200);

        // Initial state
        $msg_changes = array(100, 101, 102, 103, 104);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 0),
            101 => array('read' => 0, 'flagged' => 0),
            102 => array('read' => 0, 'flagged' => 0),
            103 => array('read' => 0, 'flagged' => 0),
            104 => array('read' => 0, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);

        $this->assertEquals($msg_changes, $folder->added());
        $this->assertEquals($flag_changes, $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals(array(), $folder->messages());
        $folder->setStatus($status);
        $folder->updateState();
        $this->assertEquals(array(), $folder->added());
        $this->assertEquals(array(), $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals($msg_changes, $folder->messages());

        // Now simulate some flag changes and new messages.
        $msg_changes = array(100, 105);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 1),
            105 => array('read' => 1, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);
        $this->assertEquals(array(105), $folder->added());
        $this->assertEquals(array(100), $folder->changed());

        $status[Horde_ActiveSync_Folder_Imap::UIDNEXT] = 106;
        $folder->setStatus($status);
        $folder->updateState();
    }

    public function testSerializationWithImapCompression()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('Trash', Horde_ActiveSync::CLASS_EMAIL);
        $status = array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => 100, Horde_ActiveSync_Folder_Imap::UIDNEXT => 47654, Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ => 200);
        $fixture = array(46653,46654,46655,46656,46657,46658,46659,46660,46661,46662,46663,46664,46665,46666,46667,46668,46669,46670,46671,46672,46673,46674,46675,46676,46677,46678,46679,46680,46681,46682,46691,46692,46693,46694,46695,46696,46697,46698,46699,46700,46701,46702,46703,46704,46705,46706,46707,46708,46709,46710,46711,46712,46713,46714,46715,46716,46717,46718,46719,46720,46721,46723,46724,46725,46726,46727,46728,46729,46730,46731,46732,46733,46734,46735,46736,46737,46738,46739,46740,46741,46742,46743,46744,46745,46746,46747,46748,46749,46750,46751,46752,46753,46754,46755,46756,46757,46758,46759,46760,46761,46762,46763,46764,46765,46766,46767,46768,46769,46770,46771,46772,46773,46774,46775,46776,46777,46778,46779,46780,46781,46782,46783,46784,46785,46786,46787,46788,46789,46790,46791,46792,46793,46794,46795,46796,46797,46798,46799,46800,46801,46802,46803,46804,46805,46806,46807,46808,46809,46810,46811,46812,46813,46814,46815,46816,46817,46818,46819,46820,46821,46822,46823,46824,46825,46826,46827,46828,46829,46830,46831,46832,46833,46834,46835,46836,46837,46838,46839,46840,46841,46842,46843,46844,46845,46846,46847,46848,46849,46850,46851,46852,46853,46854,46855,46856,46857,46858,46859,46860,46861,46862,46863,46864,46865,46866,46867,46868,46869,46870,46871,46872,46873,46874,46875,46876,46877,46878,46879,46880,46881,46883,46884,46885,46886,46887,46888,46889,46890,46891,46892,46893,46894,46895,46896,46897,46898,46899,46900,46901,46902,46903,46904,46905,46906,46907,46908,46909,46910,46911,46912,46913,46914,46915,46916,46917,46918,46919,46920,46921,46922,46923,46924,46925,46926,46927,46928,46929,46930,46931,46932,46933,46934,46935,46936,46937,46938,46939,46940,46941,46942,46943,46944,46945,46946,46947,46948,46949,46950,46951,46952,46953,46954,46955,46956,46957,46958,46959,46960,46961,46962,46963,46964,46965,46966,46967,46968,46969,46970,46971,46972,46973,46974,46975,46976,46977,46978,46979,46980,46981,46982,46983,46984,46985,46986,46987,46988,46989,46990,46991,46992,46993,46994,46995,46996,46997,46998,46999,47000,47001,47002,47003,47004,47005,47006,47007,47008,47009,47010,47011,47012,47013,47014,47015,47016,47017,47018,47019,47020,47021,47022,47023,47024,47025,47026,47027,47028,47029,47030,47031,47032,47033,47034,47035,47036,47037,47038,47039,47040,47041,47042,47043,47044,47045,47046,47047,47048,47049,47050,47051,47052,47053,47054,47055,47056,47057,47058,47059,47060,47061,47062,47063,47064,47065,47066,47067,47068,47069,47070,47071,47072,47073,47074,47075,47076,47077,47078,47079,47080,47081,47082,47083,47084,47085,47086,47087,47088,47089,47090,47091,47092,47093,47094,47095,47096,47097,47098,47099,47100,47101,47102,47103,47104,47105,47106,47107,47108,47109,47110,47111,47112,47113,47114,47115,47116,47117,47118,47119,47120,47121,47122,47123,47124,47125,47126,47127,47128,47129,47130,47131,47132,47133,47134,47135,47136,47137,47138,47139,47140,47141,47142,47143,47144,47145,47146,47147,47148,47149,47150,47151,47152,47153,47154,47155,47156,47157,47158,47159,47160,47161,47162,47163,47164,47165,47166,47167,47168,47169,47170,47171,47172,47173,47174,47175,47176,47177,47178,47179,47180,47181,47182,47183,47184,47185,47186,47187,47188,47189,47190,47191,47192,47193,47194,47195,47196,47197,47198,47199,47200,47201,47202,47203,47204,47205,47206,47207,47208,47209,47210,47211,47212,47213,47214,47215,47216,47217,47218,47219,47220,47221,47222,47223,47224,47225,47226,47227,47228,47229,47230,47231,47232,47233,47234,47235,47236,47237,47238,47239,47240,47241,47242,47243,47244,47245,47246,47247,47248,47249,47250,47251,47252,47253,47254,47255,47256,47257,47258,47259,47260,47261,47262,47263,47264,47265,47266,47267,47268,47269,47270,47271,47272,47273,47274,47275,47276,47277,47278,47279,47280,47281,47282,47283,47284,47285,47286,47287,47288,47289,47290,47291,47292,47293,47294,47295,47296,47297,47298,47299,47300,47301,47302,47303,47304,47305,47306,47307,47308,47309,47310,47311,47312,47313,47314,47315,47316,47317,47318,47319,47320,47321,47322,47323,47324,47325,47326,47327,47328,47329,47330,47331,47332,47333,47334,47335,47336,47337,47338,47339,47340,47341,47342,47343,47344,47345,47346,47347,47348,47349,47350,47351,47352,47353,47354,47355,47356,47357,47358,47359,47360,47361,47362,47363,47364,47365,47366,47367,47368,47369,47370,47371,47372,47373,47374,47375,47376,47377,47378,47379,47380,47381,47382,47383,47384,47385,47386,47387,47388,47389,47390,47391,47392,47393,47394,47395,47396,47397,47398,47399,47400,47401,47402,47403,47404,47405,47406,47407,47408,47409,47410,47411,47412,47413,47414,47415,47416,47417,47418,47419,47420,47421,47422,47423,47424,47425,47426,47427,47428,47429,47430,47431,47432,47433,47434,47435,47436,47437,47438,47439,47440,47441,47442,47443,47444,47445,47446,47447,47448,47449,47450,47451,47452,47453,47454,47455,47456,47457,47458,47459,47460,47461,47462,47463,47464,47465,47466,47467,47468,47469,47470,47471,47472,47473,47474,47475,47476,47477,47478,47479,47480,47481,47482,47483,47484,47485,47486,47487,47488,47489,47490,47491,47492,47493,47494,47495,47496,47497,47498,47499,47500,47501,47502,47503,47504,47505,47506,47507,47508,47509,47510,47511,47512,47513,47514,47515,47516,47517,47518,47519,47520,47521,47522,47523,47524,47525,47526,47527,47528,47529,47530,47531,47532,47533,47534,47535,47536,47537,47538,47539,47540,47541,47542,47543,47544,47545,47546,47547,47548,47549,47550,47551,47552,47553,47554,47555,47556,47557,47558,47559,47560,47561,47562,47563,47564,47565,47566,47567,47568,47569,47570,47571,47572,47573,47574,47575,47576,47577,47578,47579,47580,47581,47582,47583,47584,47585,47586,47587,47588,47589,47590,47591,47592,47593,47594,47595,47596,47597,47598,47599,47600,47601,47602,47603,47604,47605,47606,47607,47608,47609,47610,47611,47612,47613,47614,47615,47616,47617,47618,47619,47620,47621,47622,47623,47624,47625,47626,47627,47628,47629,47630,47631,47632,47633,47634,47635,47636,47637,47638,47639,47640,47641,47642,47643,47644,47645,47646,47647,47648,47649,47650,47651,47652,47653);
        $folder->setChanges($fixture);
        $folder->setStatus($status);
        $folder->updateState();
        $serialized = serialize($folder);
        // General test that the imap uid compression worked.
        $this->assertTrue(strlen($serialized) < 300);
        $folder = unserialize($serialized);
        // Ensure the values were preserved.
        $this->assertEquals($fixture, $folder->messages());
    }

    public function testSerializationWithoutImapCompression()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('Trash', Horde_ActiveSync::CLASS_EMAIL);
        $status = array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => 100, Horde_ActiveSync_Folder_Imap::UIDNEXT => 47654, Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ => 200);
        $fixture = array(46653,46654,46655,46656,46657,46658,46659,46660,46661,46662,46663,46664,46665,46666,46667,46668,46669,46670,46671,46672,46673,46674,46675,46676,46677,46678,46679,46680,46681,46682,46691,46692,46693,46694,46695,46696,46697,46698,46699,46700,46701,46702,46703,46704,46705,46706,46707,46708,46709,46710,46711,46712,46713,46714,46715,46716,46717,46718,46719,46720,46721,46723,46724,46725,46726,46727,46728,46729,46730,46731,46732,46733,46734,46735,46736,46737,46738,46739,46740,46741,46742,46743,46744,46745,46746,46747,46748,46749,46750,46751,46752,46753,46754,46755,46756,46757,46758,46759,46760,46761,46762,46763,46764,46765,46766,46767,46768,46769,46770,46771,46772,46773,46774,46775,46776,46777,46778,46779,46780,46781,46782,46783,46784,46785,46786);
        $folder->setChanges($fixture);
        $folder->setStatus($status);
        $folder->updateState();
        $serialized = serialize($folder);
        $folder = unserialize($serialized);
        $this->assertEquals($fixture, $folder->messages());

    }


}