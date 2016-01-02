/**
 * iPhoto2Ansel
 *
 * Copyright 2008-2016 Horde LLC (http://www.horde.org/)
 *
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */

#import <Cocoa/Cocoa.h>
#import <Quartz/Quartz.h>
#import "ExportPluginProtocol.h"

@class TURAnsel, TURAnselGallery;
@class FBProgressController;
@class TURNewGalleryController;

#if MAC_OS_X_VERSION_10_6
@interface AnselExportController : NSObject <ExportPluginProtocol, NSComboBoxDelegate> {
#else
@interface AnselExportController : NSObject <ExportPluginProtocol> {
#endif
    // Export manager passed in from iPhoto
    id <ExportImageProtocol> mExportMgr;
    
    // iPhoto asks for these
    IBOutlet NSBox <ExportPluginBoxProtocol> *mSettingsBox;
    IBOutlet NSControl *firstView;
    
    //Outlets   
    IBOutlet NSPopUpButton *mSizePopUp;
    IBOutlet NSComboBox *galleryCombo;
    IBOutlet NSTextField *statusLabel;
    IBOutlet NSProgressIndicator *spinner;
    IBOutlet NSImageView *defaultImageView;
    IBOutlet NSButton *mNewGalleryButton;
    IBOutlet NSPopUpButton *mServersPopUp;
    IBOutlet NSTextField *mImageCountLabel;
    IBOutlet NSPopUpButton *mAnselVersion;
    IBOutlet NSTextField *mVersionLabel;
    
    // Gallery View
    IBOutlet NSButton *viewGallery;
    IBOutlet NSWindow *mviewGallerySheet;
    IBOutlet NSButton *closeGalleryView;
    IBOutlet IKImageBrowserView *browserView;
    NSMutableArray *browserData;
    
    // New Server sheet
    IBOutlet NSWindow *newServerSheet;
    IBOutlet NSTextField *mServerSheetHostURL;
    IBOutlet NSTextField *mServerSheetUsername;    
    IBOutlet NSSecureTextField *mServerSheetPassword;
    IBOutlet NSTextField *mServerSheetServerNickName;
    
    // Server list
    IBOutlet NSPanel *serverListPanel;
    IBOutlet NSTableView *serverTable;
    
    // Progress struct (This one is part of the protocol, but we don't use it)
    ExportPluginProgress progress;
    
    // Currently selected server data
    NSMutableArray *anselServers;
    NSDictionary *currentServer;
    
    // This is our real progress controller (stolen from Facebook exporter).
    FBProgressController *progressController;
    
    // Mutex lock (required for the protocol, but not used)
    NSRecursiveLock *progressLock;

    BOOL cancelExport;

    TURAnsel *anselController;
    TURAnselGallery *currentGallery;
    int currentImageCount;
}

@property (readwrite, retain) TURAnselGallery *currentGallery;

// Getter/setter
- (NSWindow *)window;

// Actions
- (IBAction) showNewGallery: (id)sender;
- (IBAction) doAddServer: (id)sender;
- (IBAction) doCancelAddServer: (id)sender;
- (IBAction) clickServer: (id)sender;
- (void)     doSwapImage: (id)theImage;
- (IBAction) clickViewGallery: (id)sender;
- (IBAction) closeGalleryView: (id)sender;

// Server List
- (IBAction) closeServerList: (id)sender;
- (IBAction) removeServer: (id)sender;

// overrides
- (void)awakeFromNib;
- (void)dealloc;


@end
