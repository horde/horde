/*!
	@header		ApertureExportPlugIn.h	
	@abstract	Protocol declaration for implementing an Aperture Export Plug-in.
	@copyright	2006 Apple Inc. All rights reserved.
	 
 */

/*Copyright: 2006 Apple Computer, Inc. All rights reserved.*/


#import <PluginManager/PROAPIAccessing.h>
#import "ApertureSDKCommon.h"
#import "ApertureExportManager.h"

/*!
 @typedef		ApertureExportProgress
 @abstract		Provides values for UI progress display during export.
 @field			currentValue Current progress.
 @field			totalValue   Total to do.
 @field			message      Progress message.
 @field			indeterminateProgress   Set to YES to display an indeterminate progress bar.
 @discussion		Aperture uses the values in this structure to display the export progress in the 
 UI. Aperture starts calling this method after a plug-in calls -shouldBeginExport
 and stops calling this method after the plug-in calls -shouldFinishExport or 
 -shouldCancelExport.
 */
typedef struct
{
	unsigned long	currentValue;
	unsigned long	totalValue;
	NSString		*message;
	BOOL			indeterminateProgress;	
} ApertureExportProgress;


/*!
	@protocol		ApertureExportPlugIn
	@abstract		Specifies the methods that all Aperture export plug-ins must implement.
	@discussion		Any plug-in that does not implement the entire protocol fails when a user 
					selects the plug-in. 
 */
@protocol ApertureExportPlugIn


/*!	
	@abstract		Brokers version management between a plug-in and the host application.
	@param			apiManager The ProPlug plug-in manager object.
	@result			An initialized plug-in controller object.
	@discussion		The apiManager object is the protocol broker between a plug-in and the host. It 
					ensures that a plug-in supporting a particular version of the API is given the host 
					objects that correspond to this version. A plug-in should call -apiForProtocol on 
					the apiManager object to obtain a reference to the host export manager for use 
					throughout the export process. If the plug-in fails to obtain a reference to the 
					host export manager, it should fail to initialize. 
 */
- (id)initWithAPIManager:(id<PROAPIAccessing>)apiManager;


/*!		
	@abstract		Creates an NSBox for plug-in controls.
	@result			The subclass of NSBox that contains all of the plug-ins UI and controls.
	@discussion		Aperture currently limits the size of the export window to 1100px wide by 750px 
					tall. If the size of the window plus the settings view is too large, Aperture 
					places the view in the window and then resizes to the maximum. For best results,
					make sure your settings view can resize if necessary. (This is also useful if 
					future versions of Aperture change the maximum size.) Visually, plug-ins should 
					attempt to separate their controls from the items Aperture provides. Placing					
					plug-in controls inside an NSBox is generally the best way to do this.
 */
- (NSView *)settingsView;


/*!	
	@abstract	Returns a reference to first item of settingsView.
	@result		Returns a reference to the first item in the tab order of settingsView.
*/
- (NSView *)firstView;


/*!	
	@abstract	Returns a reference to the last item of settingsView.
	@result		Returns a reference to the last item in the tab order of settingsView.	 
*/
- (NSView *)lastView;


/*!
	@abstract	Called when the user displays a plug-in's export UI, or when the export window 
				becomes the active window. 
*/
- (void)willBeActivated;


/*!
	@abstract	Called when a plug-in's export UI is no longer the active view. 	 
*/
- (void)willBeDeactivated;


/*!	
	@abstract	Controls visibility of presets.
	@result		If a plug-in includes a file called "Export Presets.plist" in the Resources folder 
				of its bundle and if this file contains valid definitions for one or more export 
				presets, then these presets are included in the user's export preset list when the 
				plug-in is active and when the user is exporting Versions. If the plug-in also 
				returns YES from this method, then only the supplied presets are visible in the
				list. If the plug-in returns NO from this method, then all the user's existing 
				presets are visible in addition to the plug-in presets. If the plug-in does not 
				provide an "Export Presets.plist" file, or if the file does not contain any 
				valid preset definitions, then the return value of this method	has no effect and 
				Aperture displays the users existing list. 	 
*/
- (BOOL)allowsOnlyPlugInPresets;


/*!
	@abstract	Controls type of export.
	@result		Returning YES allows the user to export Master images that contain the original, unmodified camera data in its original format. Returning NO allows only Version export.
*/
- (BOOL)allowsMasterExport;


/*!	
 	@abstract	Controls type of export.
	@result		Returning YES allows the user to export Version images. (Versions contain all modifications and adjustments to an image	and are processed with the options contained in the currently-selected Export Preset.) Returning NO means Aperture only allows the export of Master images.
*/
- (BOOL)allowsVersionExport;

/*!	
 	@abstract	Controls visibility of File Naming Policy options.
	@result		Returning YES allows the user to use Aperture's File Naming Policy options to specify the file name and folder hierarchy for exported images. Returning NO hides the File Naming Policy controls on the export window. The UI elements affected include the Export Name Format popup menu, the Custom Name field, and the Example Name field.
*/
- (BOOL)wantsFileNamingControls;


/*!	
 	@abstract	Alerts plug-in that user has switched type of export.
	@discussion	This method indicates that the user has switched between Master and Version export. The plug-in can use -isMasterExport to ask the export manager for the type of export now being used. Because several version images may be based on a single master, the total count of images, along with the properties for each image, may have changed. The plug-in can use -imageCount to get the new image count before calling any other methods on the export manager.
*/
- (void)exportManagerExportTypeDidChange;


/*!
 	@abstract	Controls user prompt for destination path.
	@result		If this method returns YES, Aperture prompts the user for a destination path for an exported image or images. If this method returns NO, Aperture assumes a valid path is provided by -destinationPath.
*/
- (BOOL)wantsDestinationPathPrompt;


/*!	
 	@abstract	Returns starting directory.
	@return		If the user is prompted for a destination path (-wantsDestinationPathPrompt return YES), this method should return the starting directory for the dialog box.
*/
- (NSString *)defaultDirectory;


/*!	
 	@abstract	Returns a valid destination path for writing the file.
	@result		A valid destination path when Aperture is writing the file (-exportManagerShouldWriteImageData: returns YES) and when no user prompt is requested (-wantsDestinationPathPrompt returns NO). If nil is returned, and if the plug-in asks Aperture to write image data, it will be written to ~/Pictures/Aperture Exports/.
*/
- (NSString *)destinationPath;


/*!	
 	@abstract	Alerts plug-in that user has clicked Export button.
	@discussion	Aperture calls this plug-in method when the user clicks the Export button. The plug-in should perform any UI validations here. When the plug-in is ready to begin the export process, it should call Aperture's -shouldBeginExport method to begin the export process.
*/
- (void)exportManagerShouldBeginExport;


/*!	
	@abstract	Indicates the base directory where images will be written.
	@param		path The base file system path where images will be written. 
	@discussion	The path designations for each image will be relative to this path. The path is provided by the plug-in or by the user, depending on whether the plug-in asked Aperture to display a destination prompt or not. Because the File Naming Policy controls allow the user to specify a hierarchy of export directories based on image data, other export calls may specify a series of subdirectories. The plug-in should retain a reference to this path in order to build the full destination path.
*/
- (void)exportManagerWillBeginExportToPath:(NSString *)path;


/*!	
	@abstract	Gives the plug-in the option to selectively export an image.
	@param		index The index of an image.
	@result		Returning YES causes Aperture to export the image at the specified index. Returning NO does nothing.
	@discussion Aperture generates image data for the specified image, either reading Master data from disk or generating the Version data based on the currently-selected options.
 */
- (BOOL)exportManagerShouldExportImageAtIndex:(unsigned)index;


/*!	
	@abstract	Confirms indexed image to be exported.
	@param		index The index of an image.
	@discussion	This method confirms the index value that the plug-in returned to -exportManagerShouldExportImageAtIndex:.
*/
- (void)exportManagerWillExportImageAtIndex:(unsigned)index;


/*!	
	@abstract	Provides option for plug-in itself to handle image data.
	@param	    imageData An object containing all the data for an image and the specified index.
	@param		path The relative path where the object should be written. (The base path is set in -exportManagerWillBeginExportToPath:.) The file name is the last component of this parameter.
	@param		index The index for the image.
	@result		Returning YES instructs Aperture to write the image data. Returning NO means the plug-in itself retains the data object and can process the image as appropriate. 
	@discussion	This method is called every time the plug-in asks Aperture to export and image. NOTE: This method may be called on a secondary thread.
*/
- (BOOL)exportManagerShouldWriteImageData:(NSData*)imageData 
						   toRelativePath:(NSString*)path 
						  forImageAtIndex:(unsigned)index;


/*!	
	@abstract	Confirms that Aperture has written an image.
	@param		path The relative path where the object was written.
	@param		index	The index of the specified image.
	@discussion	This method is only called if the plug-in returned YES from the -exportManagerShouldWriteImageData: method. NOTE: This method may be called on a secondary thread.
	
*/
- (void)exportManagerDidWriteImageDataToRelativePath:(NSString *)path 
								     forImageAtIndex:(unsigned)index;


/*!
	@abstract	Confirms that Aperture has completed processing all export images.
	@discussion	Aperture calls this method once if it has completed generating and (optionally) writing data for the export images requested by the plug-in. Aperture assumes that the plug-in is still performing export operations until the plug-in calls either -shouldCancelExport or -shouldFinishExport. NOTE: This method may be called on a secondary thread.
*/
- (void)exportManagerDidFinishExport;


/*!
	@abstract	Indicates that export should be cancelled.
	@discussion	Indicates that Aperture has encountered an error, or that the user has clicked the Cancel button. Aperture has stopped exporting image data but waits for the plug-in to signal that it is ready to cancel by calling -shouldCancelExport.  Aperture then cleans up the export or progress windows.
  */
- (void)exportManagerShouldCancelExport;


/*!	
	@abstract	Displays progress information.
	@result		A pointer to a valid ApertureExportProgress structure.
	@discussion	Aperture uses the values in this structure to display the progress UI. Aperture begins calling this method after the plug-in calls -shouldBeginExport and stops calling this method after the plug-in calls -shouldFinishExport or -shouldCancelExport. 
*/
- (ApertureExportProgress *)progress;


/*!	
	@abstract	Locks ApertureExportProgress structures.
	@discussion	The plug-in should maintain an NSLock or similar object and perform any necessary locking here. Aperture will always call this method from the main thread before calling -progress.
*/
- (void)lockProgress;


/*!
	@abstract	Alerts plug-in to unlock objects.
	@discussion	The plug-in should maintain an NSLock or similar object and perform any necessary unlocking here. Aperture will always call this method from the main thread after calling -progress.
*/
- (void)unlockProgress;


@end
