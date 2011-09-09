/**
 * AnselExportController.m
 * iPhoto2Ansel
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org)
 * 
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */

#import "TURAnselKit.h"
#import "AnselExportController.h";
#import "FBProgressController.h";
#import "NSStringAdditions.h";
#import "NSDataAdditions.h";

@interface AnselExportController (PrivateAPI)
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

@implementation AnselExportController

@synthesize currentGallery;

#pragma mark -
#pragma mark init/dealloc
/**
 * Set up UI defaults
 */
- (void)awakeFromNib
{
    // Register Application Defaults
    NSMutableDictionary *defaultValues = [NSMutableDictionary dictionary];
    [defaultValues setObject: [NSNumber numberWithInt: 2]
                      forKey: TURAnselExportSize];    
    
    [defaultValues setObject: [[NSArray alloc] init] forKey: TURAnselServersKey];
    
    [defaultValues setObject: [[NSDictionary alloc] init]
                      forKey: TURAnselDefaultServerKey];
    
    NSUserDefaults *userPrefs = [NSUserDefaults standardUserDefaults];
    [userPrefs registerDefaults: defaultValues];

    // UI Defaults
    [mSizePopUp selectItemWithTag: [userPrefs integerForKey:TURAnselExportSize]];
    [self setStatusText: @"Not Connected" withColor: [NSColor redColor]];
    [spinner stopAnimation: self];
    
    // For now, update the user pref for size every time it changes - will
    // eventually put this in a pref sheet.
    [[NSNotificationCenter defaultCenter] addObserver: self
                                             selector: @selector(sizeChoiceWillChange:)
                                               name: @"NSPopUpButtonWillPopUpNotification"
                                             object: nil];
    
    // See if we have any configured servers (need a mutable array, hence the extra step here)
    anselServers = [[NSMutableArray alloc] initWithArray: [userPrefs objectForKey:TURAnselServersKey]];
    
    // Wait until iPhoto's export window is fully loaded before attempting a sheet
    [[NSNotificationCenter defaultCenter] addObserver: self
                                             selector: @selector(exportWindowDidBecomeKey:)
                                                 name: NSWindowDidBecomeKeyNotification 
                                               object :nil];
    // Version/build
    NSDictionary *info = [[NSBundle bundleForClass: [self class]] infoDictionary];
    NSString *versionString = [NSString stringWithFormat:@"%@ %@", [info objectForKey:@"CFBundleName"], [info objectForKey:@"CFBundleVersion"]];
    [mVersionLabel setStringValue: versionString];
    
    // Holds gallery's images info for the gallery preview 
    browserData = [[NSMutableArray alloc] init];
}
-(void)dealloc
{
    //anselController is released from the AnselController delegate method.
    NSLog(@"dealloc");
    [progressController release];
    [anselServers release];
    [currentServer release];
    [browserData release];
    [super dealloc];
}
- (NSWindow *)window {
    return [mExportMgr window];
}

#pragma mark -
#pragma mark Actions
- (IBAction)clickViewGallery: (id)sender
{
    [spinner startAnimation: self];
    [self setStatusText: @"Getting image list..."];
    NSMutableArray *images = [currentGallery listImages];
    if ([images count] == 0) {
        [spinner stopAnimation: self];
        [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
        return;
    }

    for (NSDictionary *image in images) {
        NSString *caption = [image objectForKey:@"caption"];
        if (caption == (NSString *)[NSNull null] || [caption length] == 0) {
            caption = [image objectForKey:@"filename"];
        }

        NSDate *theDate = [NSDate dateWithTimeIntervalSince1970: [[image objectForKey:@"original_date"] doubleValue]];
        AnselGalleryViewItem *item = [[AnselGalleryViewItem alloc] initWithURL: [NSURL URLWithString: [image objectForKey:@"url"]]
                                                                     withTitle: caption
                                                                      withDate: theDate];
        [browserData addObject: item];
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
    [NSApp endSheet: mviewGallerySheet];
    [mviewGallerySheet orderOut: nil];
    [browserData removeAllObjects];
    [browserView reloadData];
}

// Put up the newGallerySheet NSPanel
- (IBAction)showNewGallery: (id)sender
{
    TURAnselGalleryPanelController *newGalleryController;
    NSString *albumName;
    
    // Make sure we're not doing this for nothing
    if ([anselController state] == TURAnselStateConnected) {

        albumName = [mExportMgr albumNameAtIndex: 0];
        newGalleryController = [[TURAnselGalleryPanelController alloc] initWithController: anselController
                                                                          withGalleryName: albumName];
        [newGalleryController setDelegate: self];
        [newGalleryController showSheetForWindow: [self window]];
    }
}

// Remove the selected server from the saved list.
- (IBAction)removeServer: (id)sender
{
    NSTableColumn *theCol = [serverTable tableColumnWithIdentifier:@"nickname"];
    
    // We are deleting the entry for the currently selected server - make sure 
    // we disconnect.
    if ([currentServer objectForKey:TURAnselServerNickKey] == [[theCol dataCell] stringValue]) {
        [self disconnect];
    }

    NSUserDefaults *userPrefs = [NSUserDefaults standardUserDefaults]; 

    // See if the removed server is the current default.
    if ([[userPrefs objectForKey:TURAnselDefaultServerKey] objectForKey: TURAnselServerNickKey] == [[theCol dataCell] stringValue]) {
        [userPrefs setObject: nil forKey:TURAnselDefaultServerKey];
    }

    // Remove it from the servers dictionary
    [anselServers removeObjectAtIndex: [serverTable selectedRow]];
    [userPrefs setObject:anselServers forKey:TURAnselServersKey];

    [userPrefs synchronize];
    [serverTable reloadData];
    [self updateServersPopupMenu];
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
        if (currentServer != nil) {
            [self disconnect];
        }
        currentServer = [[mServersPopUp selectedItem] representedObject];
        [self doConnect];
    }
}
- (void)clickGallery
{
    [self setStatusText: @"Loading gallery data..."];
    [[self window] flushWindow];
    
    [spinner startAnimation: self];
    int row = [galleryCombo indexOfSelectedItem];
    [currentGallery setDelegate:nil];
    [currentGallery autorelease];
    currentGallery = [[anselController getGalleryByIndex:row] retain];
    [currentGallery setDelegate: self];
    [mImageCountLabel setStringValue:[NSString stringWithFormat: @"Image Count: %d", [currentGallery galleryImageCount]]];
    
    // Obtain and properly size the image for screen
    NSImage *theImage = [[NSImage alloc] initWithContentsOfURL: [currentGallery galleryKeyImageURL]];
    NSSize imageSize;
#if MAC_OS_X_VERSION_MIN_REQUIRED > MAC_OS_X_VERSION_10_5
    imageSize.width = [[theImage bestRepresentationForRect:[defaultImageView bounds] context:nil hints:nil] pixelsWide];
    imageSize.height = [[theImage bestRepresentationForRect:[defaultImageView bounds] context:nil hints:nil] pixelsHigh];
#else
    imageSize.width = [[theImage bestRepresentationForDevice:nil] pixelsWide];
    imageSize.height = [[theImage bestRepresentationForDevice:nil] pixelsHigh];
#endif
    [theImage setScalesWhenResized:YES];
    [theImage setSize:imageSize];
    [self doSwapImage: theImage];
    [theImage autorelease];
}

- (void)doSwapImage: (id)theImage
{
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];  
    [defaultImageView setImage: theImage];
    [self canExport];
    [viewGallery setEnabled: YES];
    [spinner stopAnimation: self];
}

- (IBAction) closeServerList: (id)sender
{
    [serverTable setDelegate: nil];
    [NSApp endSheet: serverListPanel];
    [serverListPanel orderOut: nil];
}

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
    [anselServers addObject: newServer];
    [NSApp endSheet: newServerSheet];
    [newServerSheet orderOut: nil];
    currentServer = [newServer retain];
    [self doConnect];
    
    // Save it to the userdefaults
    NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
    [prefs setObject:anselServers  forKey:TURAnselServersKey];   
    [prefs synchronize];
    [self updateServersPopupMenu];
    [newServer release];
}

- (IBAction)doCancelAddServer: (id)sender
{
    [NSApp endSheet: newServerSheet];
    [newServerSheet orderOut: nil];
}

#pragma mark -
#pragma mark ExportPluginProtocol
// Initialize
- (id)initWithExportImageObj:(id <ExportImageProtocol>)obj
{
    if (self = [super init])
    {
        mExportMgr = obj;
    }
    
    [mExportMgr disableControls];
    return self;
}

- (NSView <ExportPluginBoxProtocol> *)settingsView
{
    return mSettingsBox;
}
- (NSView *)firstView
{
    return firstView;
}

// These seem to be called when the plugin panel is actived/deactivated while
// export screen is open, not when the plugin is finished or the export window
// is clsoed from the Cancel button
- (void)viewWillBeActivated
{
    [self canExport];
}
- (void)viewWillBeDeactivated
{
}

// These are all pretty much moot for saving across the network, but are part
// of the protocol, so we must implement them.
- (NSString *)requiredFileType
{
    return @"";
}
- (BOOL)wantsDestinationPrompt
{
    return NO;
}
- (NSString*)getDestinationPath
{
    return @"";
}
- (NSString *)defaultFileName
{
    return @"";
}
- (NSString *)defaultDirectory
{
    return @"";
}
- (BOOL)treatSingleSelectionDifferently
{
    return NO;
}
- (BOOL)validateUserCreatedPath:(NSString*)path
{
    return NO;
}

// No movies allowed, at least for now ;)
- (BOOL)handlesMovieFiles
{
    return NO;
}

// Export was clicked in the UI.
// noop for us.
- (void)clickExport
{
}

// Export was clicked in the UI
// Do any preperations/validations and call our own privatePerformExport
// (We don't want the iPhoto progress controller).
- (void)startExport:(NSString *)path
{
    [self privatePerformExport];
}

// We use our own class for this so we don't use iPhoto's progress controller.
- (void)performExport: (NSString *)path
{
}

#pragma mark -
#pragma mark Progress (We don't use these)
- (ExportPluginProgress *)progress
{
    return &progress;
}
- (void)lockProgress
{
    [progressLock lock];
}
- (void)unlockProgress
{
    [progressLock unlock];
}
- (void)cancelExport
{
    cancelExport = YES;
}

// Return the name of our plugin.
- (NSString *)name
{
    return @"iPhoto2Ansel Export Plugin v1.0";
}

#pragma mark -
#pragma mark PrivateAPI
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

// See if we have everything we need to export...
- (void)canExport
{
    if ([anselController state] == TURAnselStateConnected) {
        [mNewGalleryButton setEnabled: YES];
        [galleryCombo setEnabled: YES];
        if (currentGallery != nil) {
            [mExportMgr enableControls];
        }
    } else {
        [mNewGalleryButton setEnabled: NO];
        [mExportMgr disableControls];   
        [galleryCombo setEnabled: NO];
        [viewGallery setEnabled: NO];
    }
}

- (void)updateServersPopupMenu
{
    NSLog(@"updateServersPopupMenu");
    [mServersPopUp removeAllItems];
    [mServersPopUp addItemWithTitle:@"(None)"];
    for (NSDictionary *server in anselServers) {
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

// Make sure we clean up from any previous connection
-(void)disconnect
{
    NSLog(@"Disconnect");
    NSLog(@"%d", [galleryCombo indexOfSelectedItem]);
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
    [currentServer release];
    currentServer = nil;
    [anselController release];
    anselController = nil;
    [self setStatusText:@"Not logged in" withColor: [NSColor redColor]];
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
    
    // NSDictionary objects cannot contain nil objects
    NSNumber *apiversion = [currentServer objectForKey: TURAnselServerVersionKey];
    if (apiversion == nil) {
        apiversion = [NSNumber numberWithInt: 1];
    }
    NSDictionary *p = [[NSDictionary alloc] initWithObjects: [NSArray arrayWithObjects:
                                                              [currentServer objectForKey:TURAnselServerEndpointKey],
                                                              [currentServer objectForKey:TURAnselServerUsernameKey],
                                                              [currentServer objectForKey:TURAnselServerPasswordKey],
                                                              apiversion,
                                                              nil]
                                                    forKeys: [NSArray arrayWithObjects:TURAnselServerEndpointKey,
                                                                                       TURAnselServerUsernameKey,
                                                                                       TURAnselServerPasswordKey,
                                                                                       TURAnselServerVersionKey,
                                                                                       nil]];
    // Create our controller
    anselController = [[TURAnsel alloc] initWithConnectionParameters:p];
    [anselController setDelegate:self];
    
    // Set up the galleryCombo
    [galleryCombo setDataSource:anselController];
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
    [anselController connect];
    [threadPool drain];
}

// Update our progress controller. Always update on the main thread.
- (void)postProgressStatus:(NSString *)status {
    [progressController performSelectorOnMainThread: @selector(setStatus:) withObject: status waitUntilDone: NO];
}

// This is our own version of performExport. We aren't using iPhoto's progress
// indicator, so we can't tell it we are running...
- (void)privatePerformExport
{
    cancelExport = NO;
    
    // Init our own progress controller.
    if (progressController == nil) {
        progressController = [[FBProgressController alloc] initWithFBExport: self];
    }
    [progressController startProgress];
    [progressController setStatus: @"Starting export"];    
    
    // Detach to a new thread for the export.
    [NSApplication detachDrawingThread: @selector(runExport) 
                              toTarget: self
                            withObject: nil];
}
        
// Runs the actual export (This is run in it's own thread)
- (void) runExport
{   
    NSAutoreleasePool *threadPool = [[NSAutoreleasePool alloc] init];
    // Init the progress bar and image counts.
    int count = [mExportMgr imageCount];
    currentImageCount = 0;   
    int i = 0;
    while (i < count && !cancelExport == YES) {
        // Don't hog memory...
        NSAutoreleasePool *pool = [[NSAutoreleasePool alloc] init];
        
        // use 99% max so the last photo upload doesn't make the progress bar look finished
        // (Thanks to Facebook exporter for this tip and code)
        double progressPercent = (double) 99. * (i+1) / count;

        [progressController performSelectorOnMainThread: @selector(setPercent:) 
                                             withObject: [NSNumber numberWithDouble: progressPercent]
                                          waitUntilDone: NO];
        
        /*** Pull out (and generate) all desired metadata before rescaling the image ***/
        CGImageSourceRef source;
        
        // Dictionary to hold all metadata
        NSMutableDictionary *metadata;
        
        // Read the image into ImageIO (the only API that supports more then just EXIF metadata)
        // Read it into a NSData object first, since we'll need that later on anyway...
        NSData *theImageData = [[NSData alloc] initWithContentsOfFile: [mExportMgr imagePathAtIndex:i]];
        
        if (!theImageData) {
            i++;
            [theImageData release];
            continue;
        }
        source = CGImageSourceCreateWithData((CFDataRef)theImageData, NULL);
        
        // Get the metadata dictionary, cast it to NSDictionary the get a mutable copy of it
        CFDictionaryRef metadataRef = CGImageSourceCopyPropertiesAtIndex(source, 0, NULL);
        NSDictionary *immutableMetadata = (NSDictionary *)metadataRef;
        metadata = [immutableMetadata mutableCopy];
        
        // Clean up some stuff we own that we don't need anymore
        immutableMetadata = nil;
        CFRelease(metadataRef);
        CFRelease(source);
        
        // Get a mutable copy of the IPTC Dictionary for the image...create a 
        // new one if one doesn't exist in the image.
        NSDictionary *iptcData = [metadata objectForKey:(NSString *)kCGImagePropertyIPTCDictionary];
        NSMutableDictionary *iptcDict = [iptcData mutableCopy];
        if (!iptcDict) {
            iptcDict = [[NSMutableDictionary alloc] init];
        }
        iptcData = nil;
        
        // Get the keywords from the image and put it into the dictionary...
        // TODO: should we check for any existing keywords first?
        NSArray *keywords = [mExportMgr imageKeywordsAtIndex: i];
        if (keywords) {
            [iptcDict setObject:keywords  forKey:(NSString *)kCGImagePropertyIPTCKeywords];
        }    
        
        // Add the title to the ObjectName field
        NSString *imageDescription = [mExportMgr imageTitleAtIndex:i];
        [iptcDict setObject:imageDescription forKey:(NSString *)kCGImagePropertyIPTCObjectName];
        
        // Add any ratings...not sure what Ansel will do with them yet, but no harm in including them
        // eh...seems like quartz mistakenly puts this value into the keywords field???
        //NSNumber *imageRating = [NSNumber numberWithInt: [mExportMgr imageRatingAtIndex:i]];
        //[iptcDict setObject:imageRating forKey:(NSString *)kCGImagePropertyIPTCStarRating];
        
        // Add the IPTC Dictionary back into the metadata dictionary....we use this
        // after the image is scaled.
        [metadata setObject:iptcDict forKey:(NSString *)kCGImagePropertyIPTCDictionary];
        
        
        // Prepare to scale the image now that we have the metadata out of it
        float imageSize;
        switch([mSizePopUp selectedTag])
        {
            case 0:
                imageSize = 320;
                break;
            case 1:
                imageSize = 640;
                break;
            case 2:
                imageSize = 1280;
                break;
            case 3:
                imageSize = 99999;
                break;
            default:
                imageSize = 1280;
                break;
        }
        
        [self postProgressStatus: [NSString stringWithFormat: @"Resizing image %d out of %d", (i+1), count]];
        
        // Don't even touch this code if we are uploading the original image
        NSData *scaledData;
        if ([mSizePopUp selectedTag] != 3) {
            
            // Put the image data into CIImage
            CIImage *im = [CIImage imageWithData: theImageData];
            
            // Calculate the scale factor and the actual dimensions.
            float yscale;
            if([im extent].size.height > [im extent].size.width) {
                yscale = imageSize / [im extent].size.height;
            }  else {
                yscale = imageSize / [im extent].size.width;
            }
            float finalW = ceilf(yscale * [im extent].size.width);
            float finalH = ceilf(yscale * [im extent].size.height);
            
            // Do an affine clamp (This essentially makes the image extent
            // infinite but removes problems with certain image sizes causing
            // edge artifacts.
            CIFilter *clamp = [CIFilter filterWithName:@"CIAffineClamp"];
            [clamp setValue:[NSAffineTransform transform] forKey:@"inputTransform"];
            [clamp setValue:im forKey:@"inputImage"];
            im = [clamp valueForKey:@"outputImage"];
            
            // Now perform the scale
            CIFilter *f = [CIFilter filterWithName: @"CILanczosScaleTransform"];
            [f setDefaults];
            [f setValue:[NSNumber numberWithFloat:yscale]
                 forKey:@"inputScale"];
            [f setValue:[NSNumber numberWithFloat:1.0]
                 forKey:@"inputAspectRatio"];
            [f setValue:im forKey:@"inputImage"];
            im = [f valueForKey:@"outputImage"];
            
            // Crop back to finite dimensions
            CIFilter *crop = [CIFilter filterWithName:@"CICrop"];
            [crop setValue:[CIVector vectorWithX:0.0
                                               Y:0.0                                               
                                               Z: finalW
                                               W: finalH]
                    forKey:@"inputRectangle"];
            
            [crop setValue: im forKey:@"inputImage"];
            im = [crop valueForKey:@"outputImage"];
   
            // Now get the image back out into a NSData object
            NSBitmapImageRep *bitmap = [[NSBitmapImageRep alloc] initWithCIImage: im];
            NSDictionary *properties = [NSDictionary dictionaryWithObjectsAndKeys:[NSNumber numberWithFloat: 1.0], NSImageCompressionFactor, nil];
            scaledData = [bitmap representationUsingType:NSPNGFileType properties:properties];	
            [bitmap release];

        } else {
            scaledData = theImageData;
        }
        
        // Now we have resized image data, put back the metadata...
        source = CGImageSourceCreateWithData((CFDataRef)scaledData, NULL);
        NSData *newData = [[NSMutableData alloc] init];
        CGImageDestinationRef destination = CGImageDestinationCreateWithData((CFMutableDataRef)newData, (CFStringRef)@"public.jpeg", 1, NULL);
        NSDictionary *destProps = [NSDictionary dictionaryWithObjectsAndKeys:[NSNumber numberWithFloat: 1.0], (NSString *)kCGImageDestinationLossyCompressionQuality, nil];
        CGImageDestinationSetProperties(destination, (CFDictionaryRef)destProps);
        
        // Get the data out of quartz (image data is in the NSData *newData object now.
        CGImageDestinationAddImageFromSource(destination, source, 0, (CFDictionaryRef)metadata);
        CGImageDestinationFinalize(destination);
        CFRelease(source);
        [self postProgressStatus: [NSString stringWithFormat: @"Encoding image %d out of %d", (i+1), count]];
        NSString *base64ImageData = [NSString base64StringFromData: newData  
                                                            length: [newData length]];
        CFRelease(destination);
        [newData release];
        [theImageData release];
        
        // Get the filename/path for this image. This returns either the most
        // recent version of the image, the original, or (if RAW) the jpeg 
        // version of the original.
        NSString *filename = [mExportMgr imageFileNameAtIndex:i];
        
        NSArray *keys = [[NSArray alloc] initWithObjects:
                         @"filename", @"description", @"data", @"type", @"tags", nil];
        
        NSString *fileType = NSFileTypeForHFSTypeCode([mExportMgr imageFormatAtIndex:i]);
        NSArray *values = [[NSArray alloc] initWithObjects:
                           filename,
                           imageDescription,
                           base64ImageData,
                           fileType,
                           keywords,
                           nil];
        
        NSDictionary *imageDataDict = [[NSDictionary alloc] initWithObjects:values
                                                                    forKeys:keys];
        NSDictionary *params = [[NSDictionary alloc] initWithObjectsAndKeys:
                                imageDataDict, @"data", 
                                [NSNumber numberWithBool:NO], @"default",
                                nil];
        
        //Start upload with current gallery.
        [self postProgressStatus: [NSString stringWithFormat: @"Uploading photo %d out of %d", (i+1), count]];
        [currentGallery uploadImageObject: params];
        [keys release];
        [values release];
        [imageDataDict release];
        [metadata release];
        [params release];
        [iptcDict release];
        [pool drain];
        i++;
    }
    
    // We are done - kill the progress controller and close the export window.
    [progressController performSelectorOnMainThread: @selector(setPercent:)
                                         withObject: nil 
                                      waitUntilDone: NO];
    
    [progressController performSelectorOnMainThread: @selector(stopProgress) 
                                         withObject: nil 
                                      waitUntilDone: YES];
    
    [progressController release];
    progressController = nil;
    
    // Need to do this ourselves since we aren't using iPhoto's progress bar.
    // Not really cancelling the export, but all this method does is close
    // the export interface and notify iPhoto that we are done.
    [mServersPopUp selectItemAtIndex: 0];
    [self disconnect];
    [mExportMgr cancelExportBeforeBeginning];
    [threadPool drain];
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

#pragma mark -
#pragma mark TURAnselDelegate

// The ansel controller is initialized, populate the gallery data
// and update the UI.
- (void)TURAnselDidInitialize
{   
    [galleryCombo reloadData];
    [galleryCombo setEnabled: true];
    [mNewGalleryButton setEnabled: true];
    [self setStatusText: @"Connected" withColor: [NSColor greenColor]];
    [self canExport];
    [spinner stopAnimation: self];
    [mServersPopUp setEnabled: true];
}

- (void)TURAnselHadError: (NSError *)error
{
    // Stop the spinner
    NSLog(@"TURAnselHadError");
    [spinner stopAnimation: self];
    [self disconnect];
    [mServersPopUp setEnabled: true];
    
    NSAlert *alert;
    // For some reason, this method doesn't pick up our userInfo dictionary...
    if ([[error userInfo] valueForKey:@"NSLocalizedDescriptionKey"] == nil) {
        alert = [[NSAlert alertWithError: error] retain];
        [mExportMgr disableControls];
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
    [mExportMgr disableControls];
    [alert release];
}

#pragma mark -
#pragma mark TURAnselGalleryDelegate
- (void)TURAnselGalleryDidUploadImage: (id *)gallery {
    if (++currentImageCount == [mExportMgr imageCount] || cancelExport == YES) {
        [currentGallery setDelegate:nil];
        [currentGallery release];
        [anselController setDelegate:nil];
        [anselController release];
        [galleryCombo setDelegate:nil];
    }
} 

#pragma mark -
#pragma mark comboBoxDelegate
- (void)comboBoxSelectionDidChange:(NSNotification *)notification
{   
    NSLog(@"comboBoxSelectionDidChange");
    [self clickGallery];    
}

#pragma mark NSTableView Datasource
- (NSInteger)numberOfRowsInTableView:(NSTableView *)aTableView
{
    return [anselServers count];
}

- (id)tableView:(NSTableView *)aTableView
    objectValueForTableColumn:(NSTableColumn *)aTableColumn
                          row:(NSInteger)rowIndex
{
    return [[anselServers objectAtIndex: rowIndex] objectForKey: [aTableColumn identifier]];
}

#pragma mark -
#pragma mark TURAnselGalleryPanel Notifications
- (void)TURAnselGalleryPanelDidAddGallery
{
    // Reload the NSComboBox and autoselect the last item.
    [galleryCombo reloadData];
    [galleryCombo selectItemAtIndex: [galleryCombo numberOfItems] - 1];
}

#pragma mark -
#pragma mark ExportController notifications
- (void)exportWindowWillClose: (NSNotification *)notification
{
    NSLog(@"exportWindowWIllClose");
    [self disconnect];
    [mServersPopUp selectItemAtIndex: 0];
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

    if ([anselServers count] == 0) {
        [self showNewServerSheet];
    } else {
        // Try to autoconnect and select the proper server in the popup.
        NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
        NSDictionary *defaultServer = [prefs objectForKey:TURAnselDefaultServerKey];
        if ([defaultServer count]) {
            currentServer = [defaultServer retain];
            int itemCount = [mServersPopUp numberOfItems];

            // C99 mode is off by default in Apple's gcc.
            int i;
            for (i = 0; i < itemCount; i++) {
                NSDictionary *menuItem = [[mServersPopUp itemAtIndex: i] representedObject];
                if ([[menuItem objectForKey: TURAnselServerNickKey] isEqual: [currentServer objectForKey:TURAnselServerNickKey]]) {
                    [mServersPopUp selectItemAtIndex: i];
                    break;
                }
            }

            [self doConnect];
        }
    }
}
- (void)sizeChoiceWillChange: (NSNotification *)notification
{
    NSInteger newSize = [mSizePopUp selectedTag];
    NSUserDefaults *userPrefs = [NSUserDefaults standardUserDefaults]; 
    [userPrefs setInteger: newSize forKey:TURAnselExportSize];
    [userPrefs synchronize];
}

#pragma mark -
#pragma mark IKImageBrowserView Datasource methods
- (NSUInteger)numberOfItemsInImageBrowser:(IKImageBrowserView *) aBrowser
{	
	return [browserData count];
}
- (id)imageBrowser:(IKImageBrowserView *) aBrowser itemAtIndex:(NSUInteger)index
{
	return [browserData objectAtIndex:index];
}
@end