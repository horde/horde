rpc.php in horde's main directory is the starting point for our (and any)
RPC call.

It determines the $serverType ("syncml" for us) and then does something
like this:

$server = Horde_RPC::factory($serverType); // [will include RPC/syncml.php and create the class therein]
$server->authorize();
$input = $server->getInput(); // [basically the HTTP POST data]
$out = $server->getResponse($input, $params);
echo $out

So the main part takes place in getResponse of
framework/RPC/RPC/syncml.php's Horde_RPC_syncml class and there in
getResponse:


First, XML_WBXML_ContentHandler is installed as an output content handler:

$this->_output = new XML_WBXML_ContentHandler();

Despite the name, this class has (almost) nothing to do with WBXML.
It's a helper to produce xml. To do this, it has 4 main methods:

1) startElement($uri, $element, $attrs) produces an <$element xlmns=$uri
   attr1=v1 ...> opening tag
2) characters($str) addes $str to the content
3) endElement($uri, $element) produces a closing tag </$element>
4) getOutput() returns the output produced so far

All subsequent code produces output by calling functions 1)-3)

After installing the output content handler, Horde_RPC_syncml::getResponse
continues with

$this->_parse($request);

do do the actual parsing and output creation and then finally

$xmlinput = $this->_output->getOutput();

to retrieve the created output from the content handler.
The name $xmlinput is misleading, it should be called xmloutput instead.

So our quest for the code continues withing the Horde_RPC_syncml's _parse
function:

It creates an XML Parser and registers the class (well, the object) itself
as element handlers:
_startElement,_endElement, and _characters, which only format the data a
bit and call startElement,endElement, and characters respectively.

Please note, that start/endElment sounding functions are used for processing
the input as well as for creation of the output.
This can be somewhat confusing. As a rule of thumb, code that produces xml
output contains reference to an output var and looks like this:

$this->_output->startElement(...);

After the XML parser is istalled, it is fired and the execution takes place
in the element handler functions.

A syncml message (input as well as output) has this structure:
<SyncML>
  <SyncHdr>
    ...stuff...
  </SyncHdr>
  <SyncBody>
    ...stuff...
  </SyncBody>
<SyncML>

the content handler in Horde_RPC_syncml delegate the work for header and
body to the two sub-content handlers SyncML_SyncMLHdr and
SyncML_SyncMLBody which reside in framework/SyncML/SyncML.php.
So at least we made it to the to the SyncML package by now...

The job of SyncML_SyncMLHdr is to read all the values in the header
and store them in a php session (custom session, not normal horde session
system) of type SyncML_State. After all header data is collected,
outputSyncHdr write a SyncHdr as output.

SyncML_SyncMLBody is another delegator. First it creates a
SyncML_Command_Status to output the status-code of the session
(authorized or not).
The content of the <syncBody> element are command(-tags): for each element
in there, an appropriate handler is created with
SyncML_Command::factory($element);
and assigned the tasks of handling this command.
So execution continues with classes in SyncML/Command/ which are
all children of SyncML_Command.

>From here, you're on your own. Just two more facts:

1)
processing of changes received from the client are handled in
SyncML/Sync.php (not to be confused with SyncML/Command/Sync.php) and
there in runSyncCommand($command) command is one of
SyncML_Command_Sync_(Add|Delete|Replace)

2)
The other way around:
creating changes on the server for the client is done after the changes
from the client have been processed. This is done in TwoWaySync.php.
Some care has to be taken to avoid that the changes that are received
from the client are considered "new changes" and echoed back to the
client. That would result in severe data duplication meltdown.


Files in SymcML:

./SyncML.php:
	definition of SyncML_ContentHandler, parent for Header- and Body-Handler
	SyncML_SyncMLHdr
		getStateFromSession() initialize session
		outputSyncHdr() write the Sync Header
	SyncML_SyncMLBody
