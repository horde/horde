//
//	ApertureToAnselExportPlugin.h
//	ApertureToAnselExportPlugin
//
//	Created by Michael Rubinsky on 8/29/09.
//	Copyright __MyCompanyName__ 2009. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import <Quartz/Quartz.h>

#import "ApertureExportManager.h"
#import "ApertureExportPlugIn.h"

@class TURAnsel, TURAnselGallery;
#if  MAC_OS_X_VERSION_10_6
@interface ApertureToAnselExportPlugin : NSObject <ApertureExportPlugIn, NSComboBoxDelegate>
#else
@interface ApertureToAnselExportPlugin : NSObject <ApertureExportPlugIn>
#endif
{
	// The cached API Manager object, as passed to the -initWithAPIManager: method.
	id _apiManager; 
	
	// The cached Aperture Export Manager object - you should fetch this from the API Manager during -initWithAPIManager:
	NSObject<ApertureExportManager, PROAPIObject> *_exportManager; 
	
	// The lock used to protect all access to the ApertureExportProgress structure
	NSLock *_progressLock;
	
	// Top-level objects in the nib are automatically retained - this array
	// tracks those, and releases them
	NSArray *_topLevelNibObjects;
	
	// The structure used to pass all progress information back to Aperture
	ApertureExportProgress exportProgress;

    // TURAnsel objects
    TURAnselGallery *_currentGallery;
    TURAnsel *_anselController;
    
	// Outlets to your plug-ins user interface
	IBOutlet NSView *settingsView;
	IBOutlet NSView *firstView;
	IBOutlet NSView *lastView;
    
    IBOutlet NSComboBox *galleryCombo;
    IBOutlet NSTextField *statusLabel;
    IBOutlet NSProgressIndicator *spinner;
    IBOutlet NSImageView *defaultImageView;
    IBOutlet NSButton *mNewGalleryButton;
    IBOutlet NSPopUpButton *mServersPopUp;
    
    // New Server sheet
    IBOutlet NSWindow *newServerSheet;
    IBOutlet NSTextField *mServerSheetHostURL;
    IBOutlet NSTextField *mServerSheetUsername;    
    IBOutlet NSSecureTextField *mServerSheetPassword;
    IBOutlet NSTextField *mServerSheetServerNickName;
    IBOutlet NSButton *mMakeNewServerDefault;
    IBOutlet NSPopUpButton *mAnselVersion;
    
    // Server list
    IBOutlet NSPanel *serverListPanel;
    IBOutlet NSTableView *serverTable;

    // Currently selected server data
    NSMutableArray *_anselServers;
    NSDictionary *_currentServer;

    // Gallery View
    IBOutlet NSButton *viewGallery;
    IBOutlet NSWindow *mviewGallerySheet;
    IBOutlet NSButton *closeGalleryView;
    IBOutlet IKImageBrowserView *browserView;
    NSMutableArray *_browserData;
    
    // Flags, counters etc...
    BOOL cancelExport;
    int _currentImageCount;
    
    BOOL isExporting;
}

- (IBAction) showNewGallery: (id)sender;
- (IBAction) clickServer: (id)sender;
- (IBAction) clickViewGallery: (id)sender;
- (IBAction) closeGalleryView: (id)sender;

// Server List
- (IBAction) closeServerList: (id)sender;
- (IBAction) removeServer: (id)sender;
- (NSWindow *)window;

// New Server View
- (IBAction) doAddServer: (id)sender;
- (IBAction) doCancelAddServer: (id)sender;
@end