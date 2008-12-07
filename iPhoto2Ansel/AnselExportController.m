//
//  AnselExportController.m
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 10/23/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//
#import "TURAnsel.h";
#import "TURAnselGallery.h";
#import "TURNewGalleryController.h";
#import "AnselExportController.h";
#import "FBProgressController.h";
#import "ImageResizer.h";

@interface AnselExportController (PrivateAPI)
- (void)connect;
- (void)postProgressStatus:(NSString *)status;
- (void)privatePerformExport;
- (void)runExport;
- (void)canExport;
- (void)newGallery;
@end

@implementation AnselExportController

@synthesize size;
@synthesize currentGallery;

#pragma mark Overrides
/**
 * Set up UI defaults
 */
- (void)awakeFromNib
{
    // UI Defaults
    [mSizePopUp selectItemWithTag:2];
    [connectedLabel setStringValue:@"Not Connected"];
    [connectedLabel setTextColor: [NSColor redColor]];
    [spinner stopAnimation:self];
}
-(void)dealloc
{
    //anselController is released from the AnselController delegate method.
    [progressController release];
    [super dealloc];
}

#pragma mark Getter Setters
- (NSWindow *)window {
    return [mExportMgr window];
}

#pragma mark Actions
// Start the connection process.
-(void)doConnect: (id)sender
{
    NSAutoreleasePool *pool = [[NSAutoreleasePool alloc] init];
    NSDictionary *p = [[NSDictionary alloc] initWithObjects: [NSArray arrayWithObjects:[anselHostURL stringValue],
                                                             [username stringValue],
                                                             [password stringValue], nil]
                                                    forKeys: [NSArray arrayWithObjects:@"endpoint", @"username", @"password", nil]];
    // Create our controller
    anselController = [[TURAnsel alloc] initWithConnectionParameters:p];
    [anselController setDelegate:self];
    
    // Set up the galleryCombo
    [galleryCombo setUsesDataSource:YES];
    [galleryCombo setDataSource:anselController];
    [galleryCombo setDelegate:self];
    [spinner startAnimation:self];
    // Detach to a new thread and do the actual login/retrieval of gallery list
    [NSApplication detachDrawingThread: @selector(connect)
                              toTarget: self 
                            withObject: nil];
    [p release];
    [pool release];
}


// Start the process of creating a new gallery. This is called from an action
// from the newGallerySheet NSPanel.
- (void)doNewGallery: (id)sender
{
    [NSApplication detachDrawingThread: @selector(newGallery)
                              toTarget: self 
                            withObject:nil]; 
}

// Put up the newGallerySheet NSPanel
- (IBAction)showNewGallery: (id)sender
{
    // Make sure we're not doing this for nothing
    if ([anselController state] == TURAnselStateConnected) {
        
        if (!newGallerySheet) {
            
            [NSBundle loadNibNamed: @"AnselGalleryPanel"
                             owner: self];
        
            [galleryNameTextField setStringValue:@"Untitled"];
        }
        
        [NSApp beginSheet: newGallerySheet
           modalForWindow: [self window]
            modalDelegate: self
           didEndSelector: @selector(sheetDidEnd:returnCode:contextInfo:)
              contextInfo: self];
    }
}

- (IBAction)cancelNewGallery: (id)sender
{
    [NSApp endSheet: newGallerySheet];
    [newGallerySheet orderOut: nil];
}

#pragma mark ExportPluginProtocol
// Initialize
- (id)initWithExportImageObj:(id <ExportImageProtocol>)obj
{
    if(self = [super init])
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
// export screen is open, not when the plugin is finished.
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

#pragma mark PrivateAPI

- (void)newGallery
{
    // Get Gallery Properties from the panel.
    NSString *galleryName = [galleryNameTextField stringValue];
    NSString *gallerySlug = [gallerySlugTextField stringValue];
    NSString *galleryDescription = [galleryDescTextField stringValue];
    
    if (!galleryName) {
        NSAlert *alert = [[NSAlert alloc] init];
        [alert setMessageText:@"Gallery names cannot be empty"];
        [alert setAlertStyle: NSCriticalAlertStyle];
        [alert beginSheetModalForWindow: [self window]
                          modalDelegate: nil 
                         didEndSelector: nil
                            contextInfo: nil];
        [alert release];
        return;
    }
    NSDictionary *params = [NSDictionary dictionaryWithObjectsAndKeys:
                            galleryName, @"name",
                            gallerySlug, @"slug",
                            galleryDescription, @"desc", nil];
    
    NSDictionary *results = [[anselController createNewGallery: params] retain];
    
    [NSApp endSheet: newGallerySheet];
    [newGallerySheet orderOut: nil];
    
    if ([anselController state] != TURAnselStateError) {
        NSAlert *alert = [[NSAlert alloc] init];
        [alert setMessageText: @"Gallery successfully created."];
        [alert beginSheetModalForWindow: [self window]
                          modalDelegate: nil
                         didEndSelector: nil
                            contextInfo: nil];
        
        // Reload the NSComboBox and autoselect the last item.
        [galleryCombo reloadData];
        [galleryCombo selectItemAtIndex: [galleryCombo numberOfItems] - 1];
        [alert release];
    }
    
    [results release];
}

// See if we have everything we need to export...
- (void)canExport
{
    if ([anselController state] == TURAnselStateConnected) {
        [newGalleryButton setEnabled: YES];
        [galleryCombo setEnabled: YES];
        if (currentGallery != nil) {
            [mExportMgr enableControls];
        }
    } else {
        [newGalleryButton setEnabled: NO];
        [mExportMgr disableControls];   
        [galleryCombo setEnabled: YES];
    }
}
// Runs in a new thread.
- (void)connect
{
    [anselController connect];
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
        
        // Prepare the image data
        NSData *theImage = [[NSData alloc] initWithContentsOfFile: [mExportMgr imagePathAtIndex:i]];
        
        CGFloat imageSize;
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
        NSData *scaledData = [ImageResizer getScaledImageFromData: theImage
                                                           toSize: NSMakeSize(imageSize, imageSize)];

        [self postProgressStatus: [NSString stringWithFormat: @"Encoding image %d out of %d", (i+1), count]];
        NSString *base64ImageData = [NSString base64StringFromData: scaledData  
                                                            length: [scaledData length]];
        
        // Get the filename/path for this image. This returns either the most
        // recent version of the image, the original, or (if RAW) the jpeg 
        // version of the original. Still need to figure out how to modify
        // the image size/quality etc... when not doing a file export.
        NSString *filename = [mExportMgr imageFileNameAtIndex:i];
        NSString *imageDescription = [mExportMgr imageTitleAtIndex:i];
        
        NSArray *keys = [[NSArray alloc] initWithObjects:
                         @"filename", @"description", @"data", @"type", nil];
        
        NSString *fileType = NSFileTypeForHFSTypeCode([mExportMgr imageFormatAtIndex:i]);
        NSArray *values = [[NSArray alloc] initWithObjects:
                           filename,
                           imageDescription,
                           base64ImageData,
                           fileType,
                           nil];
        
        NSDictionary *imageData = [[NSDictionary alloc] initWithObjects:values
                                                                forKeys:keys];
        NSDictionary *params = [[NSDictionary alloc] initWithObjectsAndKeys:
                                imageData, @"data", 
                                [NSNumber numberWithBool:NO], @"default",
                                nil];
        
        //Start upload with current gallery.
        [self postProgressStatus: [NSString stringWithFormat: @"Uploading photo %d out of %d", (i+1), count]];
        [currentGallery uploadImageObject: params];
        [keys release];
        [values release];
        [imageData release];
        [params release];
        [pool release];
        i++;
    }
    
    // We are done - kill the progress controller and close the export window.
    // TODO: Put up some kind of alert, maybe add growl support, offer to open
    // the browser window??
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
    [mExportMgr cancelExportBeforeBeginning];
}

#pragma mark TURAnselDelegate

// The ansel controller is initialized, populate the gallery data
// and update the UI.
- (void)TURAnselDidInitialize
{   
    [galleryCombo reloadData];
    [connectedLabel setStringValue:@"Connected"];
    [connectedLabel setTextColor:[NSColor greenColor]];
    [self canExport];
    [spinner stopAnimation: self];
}

- (void)TURAnselHadError: (NSError *)error
{
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

#pragma mark TURAnselGalleryDelegate
- (void)TURAnselGalleryDidUploadImage: (TURAnselGallery *)gallery {
    NSLog(@"TURAnselGalleryDidUploadImage");
    if (++currentImageCount == [mExportMgr imageCount] || cancelExport == YES) {
        [currentGallery setDelegate:nil];
        [currentGallery release];
        [anselController setDelegate:nil];
        [anselController release];
        [galleryCombo setDelegate:nil];
    }
}

#pragma mark comboBoxDelegate
- (void)comboBoxSelectionDidChange:(NSNotification *)notification
{
    int row = [galleryCombo indexOfSelectedItem];
    [currentGallery setDelegate:nil];
    [currentGallery autorelease];
    currentGallery = [[anselController getGalleryByIndex:row] retain];
    NSLog(@"The selected gallery: %@", currentGallery);
    [currentGallery setDelegate: self];
    [self canExport];
}

- (void)sheetDidEnd:(NSWindow *)sheet returnCode:(int)returnCode contextInfo:(void *)contextInfo;
{
    NSLog(@"sheetDidEnd");
}
@end