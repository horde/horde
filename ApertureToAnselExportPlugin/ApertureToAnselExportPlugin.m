/**
 *	ApertureToAnselExportPlugin.m
 *	ApertureToAnselExportPlugin
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import "ApertureToAnselExportPlugin.h"
#import "TURAnselKit.h"
#import "NSStringAdditions.h"

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
NSString * const TURAnselServerVersionKey = @"version";

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
    NSLog(@"ApertureToAnselExportPlugin: dealloc called");
    [_anselServers release];
    _anselServers = nil;

    [_anselController setDelegate:nil];
    [_anselController release];
    _anselController = nil;

    [_browserData release];
    _browserData = nil;

	// Release the top-level objects from the nib.
	[_topLevelNibObjects makeObjectsPerformSelector:@selector(release)];
	[_topLevelNibObjects release];

	[_progressLock release];
    _progressLock = nil;

	[_exportManager release];
	_exportManager = nil;

    [super dealloc];
}

#pragma mark -
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
    // Version/build
    NSDictionary *info = [[NSBundle bundleForClass: [self class]] infoDictionary];
    NSString *versionString = [NSString stringWithFormat:@"%@ %@", [info objectForKey:@"CFBundleName"], [info objectForKey:@"CFBundleVersion"]];
    [mVersionString setStringValue: versionString];
}

- (void)willBeDeactivated
{
    // noop
}

#pragma mark
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

- (void)exportManagerExportTypeDidChange
{
    // noop
}


#pragma mark -
#pragma mark Save/Path Methods

- (BOOL)wantsDestinationPathPrompt
{
	return NO;
}

- (NSString *)destinationPath
{
	return nil;
}

- (NSString *)defaultDirectory
{
	return nil;
}


#pragma mark -
#pragma mark Export Process Methods

- (void)exportManagerShouldBeginExport
{
    NSLog(@"exportManagerShouldBeginExport: %@", _currentGallery);
    if (_currentGallery == nil) {
        NSLog(@"No gallery selected.");
        NSBeginAlertSheet(@"Export failed", nil, nil, nil,
                          [_exportManager window], nil, nil,
                          nil, nil, @"No gallery selected");
        return;
    }
    [self lockProgress];
    exportProgress.totalValue = [_exportManager imageCount];
    exportProgress.currentValue = 0;
    [self unlockProgress];
    [_exportManager shouldBeginExport];
}

- (void)exportManagerWillBeginExportToPath:(NSString *)path
{
    // noop
}

// No restrictions, always return YES
- (BOOL)exportManagerShouldExportImageAtIndex:(unsigned)index
{
	return YES;
}

- (void)exportManagerWillExportImageAtIndex:(unsigned)index
{
    // noop
}

// Actually perform the upload.
- (BOOL)exportManagerShouldWriteImageData:(NSData *)imageData toRelativePath:(NSString *)path forImageAtIndex:(unsigned)index
{
    [self lockProgress];
    [exportProgress.message autorelease];
    exportProgress.message = [[NSString stringWithFormat:@"Uploading picture %d / %d",
                               index + 1, [_exportManager imageCount]] retain];
    [self unlockProgress];

    NSString *base64ImageData = [NSString base64StringFromData: imageData
                                                        length: [imageData length]];
    NSDictionary *properties = [_exportManager propertiesWithoutThumbnailForImageAtIndex: index];
    NSArray *keys = [[NSArray alloc] initWithObjects:
                     @"filename", @"description", @"data", @"type", @"tags", nil];

    /* Determine the correct filetype */
    NSDictionary *preset = [_exportManager selectedExportPresetDictionary];
    NSLog(@"Preset selected: %@",[_exportManager selectedExportPresetDictionary]);
    NSString *fileType;
    NSString *format = [preset objectForKey: @"ImageFormat"];
    if ([format isEqual: [NSNumber numberWithInt: kApertureImageFormatJPG]]) {
        fileType = @"image/jpg";
    } else if ([format isEqual: [NSNumber numberWithInt: kApertureImageFormatPNG]]) {
        fileType = @"image/png";
    } else if ([format isEqual: [NSNumber numberWithInt: kApertureImageFormatTIFF8]] ||
              [format isEqual: [NSNumber numberWithInt: kApertureImageFormatTIFF16]]) {

        // Ansel can handle converting the tiff - it is obviously unable to display the original TIFF file.
        fileType = @"image/tiff";
    } else {
        // Not supported.
        // @TODO: Need to notify user of failure of this image.
        fileType = nil;
    }

    NSLog(@"Image Type: %@", fileType);

    NSDictionary *IPTC = [properties objectForKey: kExportKeyIPTCProperties];
    
    // Use the IPTC Caption property, if available, otherwise use the version
    // name.
    NSString *caption;
    if ([IPTC objectForKey:@"Caption/Abstract"]) {
        caption = [IPTC objectForKey:@"Caption/Abstract"];
    } else {
        caption = [properties objectForKey:kExportKeyVersionName];
    }
    NSArray *values = [[NSArray alloc] initWithObjects:
                       path,
                       caption,
                       base64ImageData,
                       fileType,
                       [properties objectForKey:kExportKeyKeywords],
                       nil];

    NSDictionary *imageDataDict = [[NSDictionary alloc] initWithObjects:values
                                                                forKeys:keys];
    NSDictionary *params = [[NSDictionary alloc] initWithObjectsAndKeys:
                            imageDataDict, @"data",
                            [NSNumber numberWithBool:NO], @"default",
                            nil];


        //Start upload with current gallery.
        NSLog(@"Uploading photo %d out of %d", index, [_exportManager imageCount]);

        // Make sure we are around for all callbacks to return even if Aperture cancelled.
        [self retain];
        [_currentGallery uploadImageObject: params];
        [keys release];
        [values release];
        [imageDataDict release];
        [params release];


    // Returning NO informs Aperture that the plugin is handling the export,
    // and NOT Aperture.
	return NO;
}

// Basically just a success callback
- (void)exportManagerDidWriteImageDataToRelativePath:(NSString *)relativePath forImageAtIndex:(unsigned)index
{
	NSLog(@"exportManagerDidWriteImageDataToRelativePath %@", relativePath);
}

- (void)exportManagerDidFinishExport
{
	// You must call [_exportManager shouldFinishExport] before Aperture will put away the progress window and complete the export.
	// NOTE: You should assume that your plug-in will be deallocated immediately following this call. Be sure you have cleaned up
	// any callbacks or running threads before calling.
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
                                                    name: @"NSPopUpButtonWillPopUpNotification"
                                                  object: nil];
    [_anselController cancel];
    [_exportManager shouldCancelExport];
}


#pragma mark -
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

#pragma mark -
#pragma mark TURAnselDelegate

// The ansel controller is initialized, populate the gallery data
// and update the UI.
- (void)TURAnselDidInitialize
{
    NSLog(@"TURAnselDidInitialize");
    // Release now that the callback has completed.
    [self release];
    [galleryCombo reloadData];
    [galleryCombo setEnabled: true];
    [mNewGalleryButton setEnabled: true];
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
    [self canExport];
    [spinner stopAnimation: self];
    [mServersPopUp setEnabled: true];
}

- (void)TURAnselReceivedResults:(NSDictionary *)results forMethod:(NSString *)method
{
    NSLog(@"method: %@", method);
    NSLog(@"results: %@", results);

}

//@TODO - need to add a flag to indicate if we have a UI or not
- (void)TURAnselHadError: (NSError *)error
{
    NSLog(@"TURAnselHadError: %@", error);
    // Stop the spinner
    [spinner stopAnimation: self];
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
    [self disconnect];
    //[self release];
}

#pragma mark -
#pragma mark TURAnselGallery Delegate
- (void)TURAnselGalleryDidUploadImage:(id *)gallery
{
    [self release];
    NSLog(@"TURAnselGalleryDidUploadImage:");
    [self lockProgress];
    exportProgress.currentValue++;
    [self unlockProgress];
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
    NSImage *theImage = [[NSImage alloc] initWithContentsOfURL: [_currentGallery galleryKeyImageURL]];
    NSSize imageSize;

    // [NSImage bestRepresentationForDevice] is deprecated in 10.6, use bestRepresentationForRect:context:hints: instead if
    // we are compiling for a TARGET of 10.6
#if MAC_OS_X_VERSION_MIN_REQUIRED > MAC_OS_X_VERSION_10_5
    imageSize.width = [[theImage bestRepresentationForRect:[defaultImageView bounds] context:nil hints:nil] pixelsWide];
    imageSize.height = [[theImage bestRepresentationForRect:[defaultImageView bounds] context:nil hints:nil] pixelsHigh];
#else
    imageSize.width = [[theImage bestRepresentationForDevice:nil] pixelsWide];
    imageSize.height = [[theImage bestRepresentationForDevice:nil] pixelsHigh];
#endif
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

- (void)exportWindowDidBecomeKey: (NSNotification *)notification
{
    // Only do this once
    [[NSNotificationCenter defaultCenter] removeObserver: self
                                                    name: NSWindowDidBecomeKeyNotification
                                                  object: nil];

    [self updateServersPopupMenu];

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
#pragma mark -
#pragma mark NSTableView Datasource
- (NSInteger)numberOfRowsInTableView:(NSTableView *)aTableView
{
    return [_anselServers count];
}
- (id)tableView:(NSTableView *)aTableView
objectValueForTableColumn:(NSTableColumn *)aTableColumn
            row:(NSInteger)rowIndex
{
    return [[_anselServers objectAtIndex: rowIndex] objectForKey: [aTableColumn identifier]];
}


#pragma mark -
#pragma mark Actions
// Server setup sheet
-(IBAction)doAddServer: (id)sender
{
    NSDictionary *newServer = [[NSDictionary alloc] initWithObjectsAndKeys:
                               [mServerSheetServerNickName stringValue], TURAnselServerNickKey,
                               [mServerSheetHostURL stringValue], TURAnselServerEndpointKey,
                               [mServerSheetUsername stringValue], TURAnselServerUsernameKey,
                               [mServerSheetPassword stringValue], TURAnselServerPasswordKey,
                               [NSNumber numberWithInt: [mAnselVersion indexOfSelectedItem] + 1] , TURAnselServerVersionKey,
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
    NSLog(@"clickServer");
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
        NSLog(@"Current Server: %@", _currentServer);
        [self doConnect];
    }
}

// Show the gallery's image browser
- (IBAction)clickViewGallery: (id)sender
{
    NSLog(@"clickViewGallery");
    [_browserData removeAllObjects];
    [browserView reloadData];
    [spinner startAnimation: self];
    [self setStatusText: @"Getting image list..."];
    NSMutableArray *images = [_currentGallery listImages];
    NSLog(@"Image Count: %d", [images count]);
    if ([images count] == 0) {
        [spinner stopAnimation: self];
        [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
        return;
    }

    for (NSDictionary *image in images) {
        // The CF Web Services library can return NSNull objects for values that
        // were returned as empty or null, so we need to check for that.
        // (Even if caption is nil, caption.length will still be zero, so we
        // don't need a seperate case for that).
        NSLog(@"Image: %@", image);
        NSString *caption = [image objectForKey:@"caption"];
        if (caption == (NSString *)[NSNull null] || [caption length] == 0) {
            caption = [image objectForKey:@"name"];
        }

        NSDate *theDate = [NSDate dateWithTimeIntervalSince1970: [[image objectForKey:@"original_date"] doubleValue]];
        AnselGalleryViewItem *item = [[AnselGalleryViewItem alloc] initWithURL: [NSURL URLWithString: [image objectForKey:@"url"]]
                                                                     withTitle: caption
                                                                      withDate: theDate];
        [_browserData addObject: item];
        [item release];
    }

    [NSApp beginSheet: mviewGallerySheet
       modalForWindow: [self window]
        modalDelegate: nil
       didEndSelector: nil
          contextInfo: nil];

    [spinner stopAnimation: self];
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];

    [browserView reloadData];

}

- (IBAction) closeGalleryView: (id)sender
{
    NSLog(@"closeGalleryView");
    [NSApp endSheet: mviewGallerySheet];
    [mviewGallerySheet orderOut: nil];
}

- (IBAction) closeServerList: (id)sender
{
    [serverTable setDelegate: nil];
    [NSApp endSheet: serverListPanel];
    [serverListPanel orderOut: nil];
}

// Remove the selected server from the saved list.
- (IBAction)removeServer: (id)sender
{
    NSTableColumn *theCol = [serverTable tableColumnWithIdentifier:@"nickname"];

    // We are deleting the entry for the currently selected server - make sure
    // we disconnect.
    if ([_currentServer objectForKey:TURAnselServerNickKey] == [[theCol dataCell] stringValue]) {
        [self disconnect];
    }

    NSUserDefaults *userPrefs = [NSUserDefaults standardUserDefaults];

    // See if the removed server is the current default.
    if ([[userPrefs objectForKey:TURAnselDefaultServerKey] objectForKey: TURAnselServerNickKey] == [[theCol dataCell] stringValue]) {
        [userPrefs setObject: nil forKey:TURAnselDefaultServerKey];
    }

    // Remove it from the servers dictionary
    [_anselServers removeObjectAtIndex: [serverTable selectedRow]];
    [userPrefs setObject:_anselServers forKey:TURAnselServersKey];

    [userPrefs synchronize];
    [serverTable reloadData];
    [self updateServersPopupMenu];
}

// Put up the newGallerySheet NSPanel
- (IBAction)showNewGallery: (id)sender
{
    NSLog(@"showNewGallery:");
    TURAnselGalleryPanelController *newGalleryController;
    //NSString *albumName;

    // Make sure we're not doing this for nothing
    if ([_anselController state] == TURAnselStateConnected) {

        //albumName = [mExportMgr albumNameAtIndex: 0];
        newGalleryController = [[TURAnselGalleryPanelController alloc] initWithController: _anselController];
        [newGalleryController setDelegate: self];
        [newGalleryController showSheetForWindow: [self window]];
    }
}

#pragma mark -
#pragma mark PrivateAPI

// See if we have everything we need to export...
- (void)canExport
{
    //if ([_anselController state] == TURAnselStateConnected) {
        [mNewGalleryButton setEnabled: YES];
        [galleryCombo setEnabled: YES];
    //} else {
    if (1 == 2) {


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
}

// Start the connection process.
-(void)doConnect
{
    [self retain];
    [galleryCombo deselectItemAtIndex: [galleryCombo indexOfSelectedItem]];
    [mServersPopUp setEnabled: NO];
    [mNewGalleryButton setEnabled: NO];
    [viewGallery setEnabled: NO];
    [self setStatusText: @"Connecting..."];
    [spinner startAnimation: self];

    // NSDictionary objects cannot contain nil objects
    NSNumber *apiversion = [_currentServer objectForKey: TURAnselServerVersionKey];
    if (apiversion == nil) {
        apiversion = [NSNumber numberWithInt: 1];
    }
    NSDictionary *p = [[NSDictionary alloc] initWithObjects: [NSArray arrayWithObjects:
                                                              [_currentServer objectForKey:TURAnselServerEndpointKey],
                                                              [_currentServer objectForKey:TURAnselServerUsernameKey],
                                                              [_currentServer objectForKey:TURAnselServerPasswordKey],
                                                              apiversion,
                                                              nil]
                                                    forKeys: [NSArray arrayWithObjects:TURAnselServerEndpointKey, TURAnselServerUsernameKey, TURAnselServerPasswordKey, TURAnselServerVersionKey, nil]];
    // Create our controller
    [_anselController autorelease];
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
    NSLog(@"disconnect");
    [galleryCombo setDelegate: nil];
    if ([galleryCombo indexOfSelectedItem] >= 0) {
        [galleryCombo deselectItemAtIndex: [galleryCombo indexOfSelectedItem]];
    }
    [galleryCombo setDataSource: nil];
    [galleryCombo reloadData];
    [galleryCombo setEnabled: NO];
    [mNewGalleryButton setEnabled: NO];
    [viewGallery setEnabled: NO];
    [defaultImageView setImage: nil];
    [_currentServer release];
    _currentServer = nil;
    [_currentGallery setDelegate: nil];
    [_currentGallery release];
    _currentGallery = nil;
    [_anselController setDelegate: nil];
    [_anselController release];
    _anselController = nil;

    [self setStatusText:@"Not logged in" withColor: [NSColor redColor]];
}

#pragma mark -
#pragma mark IKImageBrowserView Datasource methods
- (NSUInteger)numberOfItemsInImageBrowser:(IKImageBrowserView *) aBrowser
{
	return [_browserData count];
}

- (id)imageBrowser:(IKImageBrowserView *) aBrowser itemAtIndex:(NSUInteger)index
{
	return [_browserData objectAtIndex:index];
}
@end
