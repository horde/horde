

SyncML Primer:
--------------

A SyncML Protocol Primer

The specification can be downloaded from www.syncml.org.
This Primer deals with SyncML 1.0.1 only.

Basically a SynML Synchronisations consists of 6 steps: Three packages sent
from the Client to the Server and three packages the other way round.

Here's a brief description of these 2*3 steps. The Chapter references refer
to XML examples for these steps in SyncML Sync Protocol, version 1.0.1(pdf)
from syncml.org.  I found these examples most helpful.

Here we go:

1a) Sync Initialization Package from Client (Chapter 4.1.1)

Client starts communication, sends authentification and device info and maybe
info about previous sync if any.

1b) Sync Initialization Package from Server (chapter 4.2.1)

Server responds with session info if authorisation was successfull, provides
device info if requested and the synchronisation type (like TwoWaySync or
SlowSync) that is suitable for this run. Basically, if both sides "remember"
the same timestamp for the previous sync run, a TwoWaySync can be used to
transfer only the differences since then. Otherwise or for initial sync,
a SlowSync is used. In that the client sends all its data to the server
which then handles them.

2a) Client Sending Modifications to Server (Chapter 5.1.1)

The client sends all its modifications since the last sync run
(or all data for SlowSync) to the server

2b) Server Sending Modifications to Client

The server incoporates the changes from the client and now sends its
modifications to the client.

3a) Data Update Status to Server (Chapter 5.3.1)

A key concept of SyncML is that client and server have their own internal
representation of the data and use different primary keys. To identify
items there has to be a mapping between the client's keys and the server's
keys. (primary keys are relative URIs in SyncML language).  This map is
maintained by the server. After the client has incoporated the servers data
in its own database it sends its new primary keys (<source><LocURI>) for the
changed data back to the server.  The server can then update its map.

3b) Map Acknowledgement from Server (Chapter 5.4.1)

Basically says: "Whoopie, we're through. See you next time".


XML Specification:


Each SyncML Packet consists of one SyncMLHdr Element an one SyncMLBody Element.

The header contains authorisation and session information.
A typical header sent from the server might look like this:

<SyncHdr>
  <VerDTD>1.0</VerDTD>
  <VerProto>SyncML/1.0</VerProto>
  <SessionID>424242424242</SessionID>
  <MsgID>2</MsgID>
  <Target>
   <LocURI>111111-00-222222-4</LocURI>
  </Target>
  <Source>
    <LocURI>http://mysyncmlserver.com/horde/rpc.php</LocURI>
  </Source>
  <RespURI>http://mysyncmlserver.com/horde/rpc.php</RespURI>
</SyncHdr>

The SyncBody contains the following elements (called "commands") as specified
in the DTD:

(Alert | Atomic | Copy | Exec | Get | Map | Put | Results | Search | Sequence
| Status | Sync)+, Final?

CmdID: each command in a packet has a unique command id like <CmdID>1</CmdId>

We discuss only Alert,Get,Put,Results,Map,Status Sync and Final here.


Get

The Get request command works similar to HTTP GET: it is intended to request
data from the communication partner.  Currently it's only use is to retrieve
"./devinf10" (or 11 for syncml 1.1) which contains information about the sync
capabilitys of the partner.

Put

Put is similar to HTTP POST: it's designed to transfer data to the
communication partner. As with get, the only use at the moment is to publish
the "./devinf10" device information to the communication partner. A typcial
first packet from the client would include a GET for the servers devinf and a
put with the client's own devinf data.

Result

The Result Element is used to respond to a GET Command and contains the
requested data, i.e. the devinf data.

Status

In General, for each command there must be a status response from the other
side. (For exception see the spec.)

The Status includes a CmdID (like any command).  It has a MsgRef and CmdRef to
identify the command it responds to: MsgRef identifies the packet (given in
the Header) and CmdRef the CmdId of the original command. There's also a <cmd>
Element to specify the type

<Status>
  <CmdID>3</CmdID>
  <MsgRef>1</MsgRef>
  <CmdRef>2</CmdRef>
  <Cmd>Put</Cmd>
  <SourceRef>./devinf10</SourceRef>
  <Data>200</Data> <!--Statuscode for OK-->
</Status>

Sync

Alert

Sync and Alert is where the action takes place. Unfortunately the primer is
not yet finished.
Stay tuned or check the Spec yourself...
