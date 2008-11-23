/*
     File:       ExportPluginProtocol.h
 
     Contains:   iPhoto Plug-ins interfaces: Export plugin expected format and methods
 
     Version:    Technology: iPhoto
                 Release:    1.0
 
     Copyright:  © 2002-2007 by Apple Inc. All rights reserved.
 
     Bugs?:      For bug reports, consult the following page on
                 the World Wide Web:
 
                     http://developer.apple.com/bugreporter/
*/

#import <Cocoa/Cocoa.h>

#import "ExportImageProtocol.h"
#import "ExportPluginBoxProtocol.h"

@class NSView;

//------------------------------------------------------------------------------
// Definitions
//------------------------------------------------------------------------------
typedef struct
{
	unsigned long	currentItem;
	unsigned long	totalItems;
	NSString		*message;
	BOOL			indeterminateProgress;	// YES, if use indeterminate progress bar
	BOOL			shouldCancel;			// Can be set by the progressSelector
	BOOL			shouldStop;
} ExportPluginProgress;

//------------------------------------------------------------------------------
@protocol ExportPluginProtocol

//------------------------------------------------------------------------------
// Public methods
//------------------------------------------------------------------------------
// Initialize with an image exporter
- (id)initWithExportImageObj:(id <ExportImageProtocol>)obj;

	// Return the view that you want displayed
- (NSView<ExportPluginBoxProtocol> *)settingsView;
- (NSView *)firstView;	// First focus item
//- (NSControl *)lastView;	// Last focus item

	// Gain/Lose focus
- (void)viewWillBeActivated;
- (void)viewWillBeDeactivated;

	// Required file type for saving
- (NSString *)requiredFileType;

	// If the plugin wants to handle prompting for a desitnation,
	// return NO to wantsDestinationPrompt and provide a path
	// in getDestinationPath. If getDestinationPath returns nil,
	// then the export should be canceled.
- (BOOL)wantsDestinationPrompt;
- (NSString*)getDestinationPath;

	// Defaults for save prompt
- (NSString *)defaultFileName;
- (NSString *)defaultDirectory;

	// Some plugins (currently just the File Exporter) need to be able
	// to tell the controller to work a little differently if the
	// user is exporting just one image.
- (BOOL)treatSingleSelectionDifferently;

	// ask if the plugin can handle movies
- (BOOL)handlesMovieFiles;

	// let each plugin decide what to do if the user types a path into the export dialog
- (BOOL)validateUserCreatedPath:(NSString*)path;

	// If the user hits Enter/Return, pass this action back to the
	// controller as a click on the Export button.
- (void)clickExport;

	// This selector may be called from within a separate NSThread.
	// Prepare to export with the current settings to <path>.
	// Must call ExportController's startExport to begin
- (void)startExport:(NSString *)path;

	// This selector may be called from within a separate NSThread.
	// Perform the export with the current settings to <path>.
	// It will periodically send the <callback> selector a 
	// progress message in the form of a pointer to a ExportPluginProgress structure.
- (void)performExport:(NSString *)path;

// Updating progress information: lockProgress before changing values in ExportPluginProgress
- (ExportPluginProgress *)progress;
- (void)lockProgress;
- (void)unlockProgress;

	// Called when the user cancels
- (void)cancelExport;

- (NSString *)name;

	//------------------------------------------------------------------------------
@end
