//
//	ApertureToAnselExportPlugin.m
//	ApertureToAnselExportPlugin
//
//	Created by Michael Rubinsky on 8/29/09.
//	Copyright __MyCompanyName__ 2009. All rights reserved.
//

#import "ApertureToAnselExportPlugin.h"
#import "TURAnsel.h"
#import "TURAnselGallery.h"

@interface ApertureToAnselExportPlugin (PrivateAPI)
- (void)showNewServerSheet;
- (void)showServerListPanel;
- (void)updateServersPopupMenu;
- (void)doConnect;
- (void)connect;
- (void)disconnect;
- (void)postProgressStatus:(NSString *)status;
- (void)privatePerformExport;
- (void)runExport;
- (void)canExport;
- (void)setStatusText: (NSString *)message withColor:(NSColor *)theColor;
- (void)setStatusText: (NSString *)message;
@end


// User default keys
NSString * const TURAnselServersKey = @"AnselServers";
NSString * const TURAnselExportSize = @"AnselExportSize";
NSString * const TURAnselDefaultServerKey = @"AnselDefaultServer";

// Server property keys
NSString * const TURAnselServerNickKey = @"nickname";
NSString * const TURAnselServerEndpointKey = @"endpoint";
NSString * const TURAnselServerUsernameKey = @"username";
NSString * const TURAnselServerPasswordKey = @"password";

@implementation ApertureToAnselExportPlugin

//---------------------------------------------------------
// initWithAPIManager:
//
// This method is called when a plug-in is first loaded, and
// is a good point to conduct any checks for anti-piracy or
// system compatibility. This is also your only chance to
// obtain a reference to Aperture's export manager. If you
// do not obtain a valid reference, you should return nil.
// Returning nil means that a plug-in chooses not to be accessible.
//---------------------------------------------------------
 - (id)initWithAPIManager:(id<PROAPIAccessing>)apiManager
{
	if (self = [super init]) {
		_apiManager	= apiManager;
		_exportManager = [[_apiManager apiForProtocol:@protocol(ApertureExportManager)] retain];
		if (!_exportManager) {
			return nil;
        }
		
		_progressLock = [[NSLock alloc] init];
        
        // Register Application Defaults
        NSMutableDictionary *defaultValues = [NSMutableDictionary dictionary];
        [defaultValues setObject: [NSNumber numberWithInt: 2]
                          forKey: TURAnselExportSize];    
        
        [defaultValues setObject: [[NSArray alloc] init] forKey: TURAnselServersKey];
        
        [defaultValues setObject: [[NSDictionary alloc] init]
                          forKey: TURAnselDefaultServerKey];
        
        NSUserDefaults *userPrefs = [NSUserDefaults standardUserDefaults];
        [userPrefs registerDefaults: defaultValues];
        [self setStatusText: @"Not Connected" withColor: [NSColor redColor]];
        [spinner stopAnimation: self];
        
        // See if we have any configured servers (need a mutable array, hence the extra step here)
        _anselServers = [[NSMutableArray alloc] initWithArray: [userPrefs objectForKey:TURAnselServersKey]];
        
        // Wait until Aperture's export window is fully loaded before attempting a sheet
        [[NSNotificationCenter defaultCenter] addObserver: self
                                                 selector: @selector(exportWindowDidBecomeKey:)
                                                     name: NSWindowDidBecomeKeyNotification 
                                                  object :nil];
        
        // Holds gallery's images info for the gallery preview 
        _browserData = [[NSMutableArray alloc] init];
        
        [self lockProgress];
        exportProgress.currentValue = 0;
        exportProgress.totalValue = 0;
        [self unlockProgress];
	}

	NSLog(@"initWithAPIManager completed");
	return self;
}

- (void)dealloc
{
    [_anselServers release];
    [_anselController setDelegate:nil];
    [_anselController release];
    [_browserData release];
    
	// Release the top-level objects from the nib.
	[_topLevelNibObjects makeObjectsPerformSelector:@selector(release)];
	[_topLevelNibObjects release];
	[_progressLock release];
	[_exportManager release];
	[super dealloc];
}

#pragma mark -
// UI Methods
#pragma mark UI Methods

- (NSView *)settingsView
{
    NSLog(@"settingsView");
	if (nil == settingsView)
	{
		// Load the nib using NSNib, and retain the array of top-level objects so we can release
		// them properly in dealloc
		NSBundle *myBundle = [NSBundle bundleForClass:[self class]];
		NSNib *myNib = [[NSNib alloc] initWithNibNamed:@"ApertureToAnselExportPlugin" bundle:myBundle];
		if ([myNib instantiateNibWithOwner:self topLevelObjects:&_topLevelNibObjects])
		{
			[_topLevelNibObjects retain];
		}
		[myNib release];
	}
	
	return settingsView;
}

- (NSView *)firstView
{
	return firstView;
}

- (NSView *)lastView
{
	return lastView;
}

- (void)willBeActivated
{
    NSLog(@"willBeActivated");
}

- (void)willBeDeactivated
{
    NSLog(@"willBeDeactivated");
}

#pragma mark
// Aperture UI Controls
#pragma mark Aperture UI Controls

- (BOOL)allowsOnlyPlugInPresets
{
	return NO;	
}

- (BOOL)allowsMasterExport
{
	return NO;	
}

- (BOOL)allowsVersionExport
{
	return YES;	
}

- (BOOL)wantsFileNamingControls
{
	return NO;	
}

- (void)exportManagerExportTypeDidChange{}


#pragma mark -
// Save Path Methods
#pragma mark Save/Path Methods

- (BOOL)wantsDestinationPathPrompt
{
	return NO;
}

- (NSString *)destinationPath
{
	return @"/tmp";
}

- (NSString *)defaultDirectory
{
	return nil;
}


#pragma mark -
// Export Process Methods
#pragma mark Export Process Methods

- (void)exportManagerShouldBeginExport
{	// You must call [_exportManager shouldBeginExport] here or elsewhere before Aperture will begin the export process
    NSLog(@"exportManagerShouldBeginExport");
    [self lockProgress];
    exportProgress.totalValue = [_exportManager imageCount];
    exportProgress.currentValue = 0;
    [self unlockProgress];
    [_exportManager shouldBeginExport];
}

- (void)exportManagerWillBeginExportToPath:(NSString *)path {}

- (BOOL)exportManagerShouldExportImageAtIndex:(unsigned)index
{
	return YES;
}

- (void)exportManagerWillExportImageAtIndex:(unsigned)index
{
	
}

- (BOOL)exportManagerShouldWriteImageData:(NSData *)imageData toRelativePath:(NSString *)path forImageAtIndex:(unsigned)index
{
    [self lockProgress];
    [exportProgress.message autorelease];
    exportProgress.message = [[NSString stringWithFormat:@"Uploading picture %d / %d",
                               index + 1, [_exportManager imageCount]] retain];
    exportProgress.currentValue++;
    
    [self unlockProgress];
    NSString *base64ImageData = [NSString base64StringFromData: imageData  
                                                        length: [imageData length]];
    NSDictionary *properties = [_exportManager propertiesWithoutThumbnailForImageAtIndex: index];
    NSArray *keys = [[NSArray alloc] initWithObjects:
                     @"filename", @"description", @"data", @"type", @"tags", nil];
    
    NSString *fileType = @"jpeg"; //@TODO
    NSArray *values = [[NSArray alloc] initWithObjects:
                       path,
                       [properties objectForKey: kExportKeyVersionName], //imagedescription
                       base64ImageData,
                       fileType,
                       [properties objectForKey:kExportKeyKeywords], //keywords
                       nil];
    
    
    
    NSDictionary *imageDataDict = [[NSDictionary alloc] initWithObjects:values
                                                                forKeys:keys];
    NSDictionary *params = [[NSDictionary alloc] initWithObjectsAndKeys:
                            imageDataDict, @"data", 
                            [NSNumber numberWithBool:NO], @"default",
                            nil];
//    
    //Start upload with current gallery.
    NSLog(@"Uploading photo %d out of %d", index, [_exportManager imageCount]);
    [_currentGallery uploadImageObject: params];
    [keys release];
    [values release];
    [imageDataDict release];
    //[metadata release];
    [params release];
    //[iptcDict release];

	return NO;	
}

- (void)exportManagerDidWriteImageDataToRelativePath:(NSString *)relativePath forImageAtIndex:(unsigned)index
{
	NSLog(@"exportManagerDidWriteImageDataToRelativePath %d", index);
}

- (void)exportManagerDidFinishExport
{
	// You must call [_exportManager shouldFinishExport] before Aperture will put away the progress window and complete the export.
	// NOTE: You should assume that your plug-in will be deallocated immediately following this call. Be sure you have cleaned up
	// any callbacks or running threads before calling.
    [[NSNotificationCenter defaultCenter] removeObserver: self
                                                   name: NSWindowWillCloseNotification
                                                 object: nil];
    [[NSNotificationCenter defaultCenter] removeObserver: self 
                                                    name: @"NSPopUpButtonWillPopUpNotification"
                                                  object: nil];
    
    [_exportManager shouldFinishExport];
}

- (void)exportManagerShouldCancelExport
{
	// You must call [_exportManager shouldCancelExport] here or elsewhere before Aperture will cancel the export process
	// NOTE: You should assume that your plug-in will be deallocated immediately following this call. Be sure you have cleaned up
	// any callbacks or running threads before calling.
    [[NSNotificationCenter defaultCenter] removeObserver: self
                                                    name: NSWindowWillCloseNotification
                                                  object: nil];
    [[NSNotificationCenter defaultCenter] removeObserver: self 
                                                    name: @"NSPopUpButtonWillPopUpNotification"
                                                  object: nil];
    [_exportManager shouldCancelExport];
}


#pragma mark -
	// Progress Methods
#pragma mark Progress Methods

- (ApertureExportProgress *)progress
{
	return &exportProgress;
}

- (void)lockProgress
{	
	if (!_progressLock) {
		_progressLock = [[NSLock alloc] init];
    }
	[_progressLock lock];
}

- (void)unlockProgress
{
	[_progressLock unlock];
}

- (NSWindow *)window
{
    return [_exportManager window];
}

#pragma mark NSPopUpButton Notification Handlers
- (void) NSPopUpWillPopUp:(id)theButton
{
    // Remember the previous selection before it changes.
    // The 'clickServer' action will handle what to do with the selection.
    NSLog(@"test");
    mIndexOfPreviouslySelectedServer = [mServersPopUp indexOfSelectedItem];
}

#pragma mark -
#pragma mark TURAnselDelegate

// The ansel controller is initialized, populate the gallery data
// and update the UI.
- (void)TURAnselDidInitialize
{   
    NSLog(@"TURAnselDidInitialize");
    [galleryCombo reloadData];
    [galleryCombo setEnabled: true];
    [mNewGalleryButton setEnabled: true];
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
    [self canExport];
    [spinner stopAnimation: self];
    [mServersPopUp setEnabled: true];
}

//@TODO - need to add a flag to indicate if we have a UI or not
- (void)TURAnselHadError: (NSError *)error
{
    NSLog(@"TURAnselHadError");
    // Stop the spinner
    [spinner stopAnimation: self];
    [self disconnect];
    [mServersPopUp setEnabled: true];
    
    NSAlert *alert;
    // For some reason, this method doesn't pick up our userInfo dictionary...
    if ([[error userInfo] valueForKey:@"NSLocalizedDescriptionKey"] == nil) {
        alert = [[NSAlert alertWithError: error] retain];
    } else {
        alert = [[NSAlert alloc] init];
        [alert setAlertStyle:NSWarningAlertStyle];
        [alert setMessageText:[[error userInfo] valueForKey:@"NSLocalizedDescriptionKey"]];
        if ([[error userInfo] valueForKey: @"NSLocalizedRecoverySuggestionErrorKey"] != nil) {
            [alert setInformativeText: [[error userInfo] valueForKey: @"NSLocalizedRecoverySuggestionErrorKey"]];
        }
    }
    
    [alert beginSheetModalForWindow:[self window]
                      modalDelegate:nil
                     didEndSelector:nil
                        contextInfo:nil];
    [alert release];
}

#pragma mark -
#pragma mark comboBoxDelegate
- (void)comboBoxSelectionDidChange:(NSNotification *)notification
{    
    [spinner startAnimation: self];
    [self setStatusText: @"Loading gallery data..."];
    int row = [galleryCombo indexOfSelectedItem];
    [_currentGallery setDelegate:nil];
    [_currentGallery autorelease];
    _currentGallery = [[_anselController getGalleryByIndex:row] retain];
    [_currentGallery setDelegate: self];
    
    // Obtain and properly size the image for screen
    NSImage *theImage = [[NSImage alloc] initWithContentsOfURL: [_currentGallery galleryDefaultImageURL]];
    NSSize imageSize;
    imageSize.width = [[theImage bestRepresentationForDevice:nil] pixelsWide];
    imageSize.height = [[theImage bestRepresentationForDevice:nil] pixelsHigh];    
    [theImage setScalesWhenResized:YES];
    [theImage setSize:imageSize];
    
    // Show it
    [defaultImageView setImage: theImage];
    
    [theImage release];
    [self canExport];
    [viewGallery setEnabled: YES];
    [spinner stopAnimation: self];
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
}

#pragma mark export notifications
- (void)exportWindowWillClose: (NSNotification *)notification
{
    [mServersPopUp selectItemAtIndex: 0];
    [self disconnect];
    [[NSNotificationCenter defaultCenter] removeObserver: self
                                                    name: NSWindowWillCloseNotification
                                                  object: nil];
}

- (void)exportWindowDidBecomeKey: (NSNotification *)notification
{
    NSLog(@"exportWindowDidBecomeKey");
    // Register for the close notification
    [[NSNotificationCenter defaultCenter] addObserver: self
                                             selector: @selector(exportWindowWillClose:)
                                                 name: NSWindowWillCloseNotification
                                              object :nil];
    
    // Only do this once
    [[NSNotificationCenter defaultCenter] removeObserver: self
                                                    name: NSWindowDidBecomeKeyNotification
                                                  object: nil];
    
    [self updateServersPopupMenu];
    
    // Register for notifications
    [[NSNotificationCenter defaultCenter] addObserver: self
                                             selector: @selector(NSPopUpWillPopUp:)
                                                 name:@"NSPopUpButtonWillPopUpNotification"
                                               object: nil];
    if ([_anselServers count] == 0) {
        [self showNewServerSheet];
    } else {
        // Try to autoconnect and select the proper server in the popup.
        NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
        NSDictionary *defaultServer = [prefs objectForKey:TURAnselDefaultServerKey];
        if ([defaultServer count]) {
            _currentServer = [defaultServer retain];
            int itemCount = [mServersPopUp numberOfItems];
            
            // C99 mode is off by default in Apple's gcc.
            int i;
            for (i = 0; i < itemCount; i++) {
                NSDictionary *menuItem = [[mServersPopUp itemAtIndex: i] representedObject];
                if ([[menuItem objectForKey: TURAnselServerNickKey] isEqual: [_currentServer objectForKey:TURAnselServerNickKey]]) {
                    [mServersPopUp selectItemAtIndex: i];
                    break;
                }
            }
            
            [self doConnect];
        }
    }
}
// Server setup sheet
-(IBAction)doAddServer: (id)sender
{
    // TODO: Sanity checks
    NSDictionary *newServer = [[NSDictionary alloc] initWithObjectsAndKeys:
                               [mServerSheetServerNickName stringValue], TURAnselServerNickKey,
                               [mServerSheetHostURL stringValue], TURAnselServerEndpointKey,
                               [mServerSheetUsername stringValue], TURAnselServerUsernameKey,
                               [mServerSheetPassword stringValue], TURAnselServerPasswordKey,
                               nil];
    [_anselServers addObject: newServer];
    [NSApp endSheet: newServerSheet];
    [newServerSheet orderOut: nil];
    _currentServer = [newServer retain];
    [self doConnect];
    
    // Save it to the userdefaults
    NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
    [prefs setObject:_anselServers  forKey:TURAnselServersKey];   
    
    int defaultState = [mMakeNewServerDefault state];
    if (defaultState == NSOnState) {
        [prefs setObject: _currentServer forKey: TURAnselDefaultServerKey];
    }
    
    [prefs synchronize];
    [self updateServersPopupMenu];
    [newServer release];
}

- (IBAction)doCancelAddServer: (id)sender
{
    [NSApp endSheet: newServerSheet];
    [newServerSheet orderOut: nil];
}

// Action sent by the server pop up menu
- (IBAction)clickServer: (id)sender
{
    // Are we set to "none" now?
    if ([mServersPopUp indexOfSelectedItem] == 0) {
        [self disconnect];
    } else if ([mServersPopUp indexOfSelectedItem] == [mServersPopUp numberOfItems] - 1) {
        // Server list
        [self showServerListPanel];
    } else if ([mServersPopUp indexOfSelectedItem] == [mServersPopUp numberOfItems] - 2) {
        // New Server
        [self showNewServerSheet];
    } else if (![[[mServersPopUp selectedItem] title] isEqual:@"(None)"]) {
        // Connect to a server
        if (_currentServer != nil) {
            [self disconnect];
        }
        _currentServer = [[mServersPopUp selectedItem] representedObject];
        [self doConnect];
    }
}

#pragma mark -
#pragma mark PrivateAPI

// See if we have everything we need to export...
- (void)canExport
{
    if ([_anselController state] == TURAnselStateConnected) {
        [mNewGalleryButton setEnabled: YES];
        [galleryCombo setEnabled: YES];
    } else {
        [mNewGalleryButton setEnabled: NO];
        [galleryCombo setEnabled: NO];
        [viewGallery setEnabled: NO];
    }
}

- (void)setStatusText: (NSString *)message withColor:(NSColor *)theColor
{
    [statusLabel setStringValue: message];
    [statusLabel setTextColor: theColor];
}

- (void)setStatusText: (NSString *)message
{
    [statusLabel setStringValue: message];
    [statusLabel setTextColor: [NSColor blackColor]];
}

- (void)updateServersPopupMenu
{
    [mServersPopUp removeAllItems];
    [mServersPopUp addItemWithTitle:@"(None)"];
    for (NSDictionary *server in _anselServers) {
        NSMenuItem *menuItem = [[NSMenuItem alloc] initWithTitle: [server objectForKey: TURAnselServerNickKey]
                                                          action: nil
                                                   keyEquivalent: @""];
        [menuItem setRepresentedObject: server];
        [[mServersPopUp menu] addItem: menuItem];
    }
    
    // add separator
    [[mServersPopUp menu] addItem:[NSMenuItem separatorItem]];
    
    // add Add Gallery... and Edit List... options
    [mServersPopUp addItemWithTitle:@"Add Server..."];
    [mServersPopUp addItemWithTitle:@"Edit Server List..."];
    
    // fix selection
    [mServersPopUp selectItemAtIndex:0];
}

- (void) showNewServerSheet
{
    [NSApp beginSheet: newServerSheet
       modalForWindow: [self window]
        modalDelegate: nil
       didEndSelector: nil
          contextInfo: nil];
    
    // Make sure these are cleared.
    [mServerSheetHostURL setStringValue: @""];
    [mServerSheetUsername setStringValue: @""];
    [mServerSheetPassword setStringValue: @""];
    [mServerSheetServerNickName setStringValue: @""];
}

- (void) showServerListPanel
{
    [NSApp beginSheet: serverListPanel
       modalForWindow: [self window]
        modalDelegate: nil
       didEndSelector: nil
          contextInfo: nil];
    
    [serverTable setDelegate: self];
}

// Start the connection process.
-(void)doConnect
{
    [galleryCombo deselectItemAtIndex: [galleryCombo indexOfSelectedItem]];
    [mServersPopUp setEnabled: NO];
    [mNewGalleryButton setEnabled: NO];
    [viewGallery setEnabled: NO];
    [self setStatusText: @"Connecting..."];
    [spinner startAnimation: self];
    NSAutoreleasePool *pool = [[NSAutoreleasePool alloc] init];
    NSDictionary *p = [[NSDictionary alloc] initWithObjects: [NSArray arrayWithObjects:
                                                              [_currentServer objectForKey:TURAnselServerEndpointKey],
                                                              [_currentServer objectForKey:TURAnselServerUsernameKey],
                                                              [_currentServer objectForKey:TURAnselServerPasswordKey],
                                                              nil]
                                                    forKeys: [NSArray arrayWithObjects:@"endpoint", @"username", @"password", nil]];
    // Create our controller
    _anselController = [[TURAnsel alloc] initWithConnectionParameters:p];
    [_anselController setDelegate:self];
    
    // Set up the galleryCombo
    [galleryCombo setDataSource:_anselController];
    [galleryCombo setDelegate:self];
    [spinner startAnimation:self];
    // Detach to a new thread and do the actual login/retrieval of gallery list
    [NSApplication detachDrawingThread: @selector(connect)
                              toTarget: self 
                            withObject: nil];
    [p release];
    [pool drain];
}
// Runs in a new thread.
- (void)connect
{
    NSAutoreleasePool *threadPool = [[NSAutoreleasePool alloc] init];
    [_anselController connect];
    [threadPool drain];
}

// Make sure we clean up from any previous connection
-(void)disconnect
{
    [galleryCombo deselectItemAtIndex: [galleryCombo indexOfSelectedItem]];
    [galleryCombo setDelegate: nil];
    [galleryCombo setDataSource: nil];
    [galleryCombo reloadData];
    [galleryCombo setEnabled: NO];
    [mNewGalleryButton setEnabled: NO];
    [viewGallery setEnabled: NO];
    [defaultImageView setImage: nil];
    [_currentServer release];
    _currentServer = nil;
    [_anselController release];
    _anselController = nil;
    [self setStatusText:@"Not logged in" withColor: [NSColor redColor]];
}
@end
