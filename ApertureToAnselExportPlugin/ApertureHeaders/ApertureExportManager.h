/*!
	@header			ApertureExportManager.h
	@copyright		2006 Apple Inc. All rights reserved.
	@abstract		Protocol declaration for Aperture's export interface. 
	@discussion		Aperture export plug-ins use these methods to control the export process. Version 1.0.
*/

#import "ApertureSDKCommon.h"

/*!
	@protocol		ApertureExportManager
	@discussion		Protocol definition for the Aperture export interface. You use this protocol to communicate with the Aperture application.
 */
@protocol ApertureExportManager


/*!
	@abstract		Returns the number of images the user wants to export.
	@result			An unsigned integer indicating the number of images the user wants to export. 
	@discussion		Note that the image count may change if the user is allowed to choose between Master and Version export (see -allowsMasterExport). If the user switches, the Aperture export manager sends the plug-in a message (see -exportManagerExportTypeDidChange). The plug-in should then call -imageCount to make sure the number of images to export is correct.
*/
- (unsigned)imageCount;


/*!	
	@abstract		Returns a dictionary containing all the properties for an image.
	@param			index  The index of the target image.
	@result			A dictionary containing the available properties for the specified image. The returned dictionary contains a thumbnail image whose size is kExportThumbnailSizeMini.
	@discussion		For Master images, the returned properties come from the original import properties. These include properties from the image file and camera as well as any IPTC values the user added on import. The keys contained in the properties dictionary are defined at the beginning of this header file.
 */
- (NSDictionary *)propertiesForImageAtIndex:(unsigned)index;


/* New in Aperture 1.5.1, Part of ApertureExportManager version 2 */
/*!	
	@abstract		Returns a dictionary containing all the properties for an image, but without a value for the kExportKeyThumbnailImage key.
	@param			index  The index of the target image.
	@result			A dictionary containing all the available properties for the specified image, except a thumbnail. You may obtain a thumbnail separately using the -thumbnailImageForImageAtIndex:size: method.
	@discussion		For Master images, the returned properties come from the original import properties. These include properties from the image file and camera as well as any IPTC values the user added on import. The keys contained in the properties dictionary are defined at the beginning of this header file. This method is available in version 2 of the ApertureExportManager protocol in Aperture 1.5.1. Your plug-in must specify support for this version in its Info.plist, and you can ask the export manager which version it is by calling -conformsToProtocol:version: (defined in the PROAPIObject PROAPIAccessing.h)
*/
- (NSDictionary *)propertiesWithoutThumbnailForImageAtIndex:(unsigned)index;


/* New in version 2 of the ApertureExportManager protocol, as part of Aperture 1.5.1  */
/*!	
	@abstract		(Version 2) Returns a small version of the specified image. 
	@param			index The index of the target image
	@param			size The constant indicating how large of a thumbnail image Aperture should return.
	@result			An image object containing the thumbnail data
	@discussion		New in version 2 of the ApertureExportManager protocol. Supported by Aperture 1.5.1 and later. For master images, this method may return nil. You may check if the Aperture your plug-in is running in supports version 2 by using the PROAPIAccessing protocol.
*/
- (NSImage *)thumbnailForImageAtIndex:(unsigned)index
								 size:(ApertureExportThumbnailSize)size;

/*!	
	@abstract		Returns the key-value pairs defining the currently-selected export presets for a Version export.
	@result			A pointer to an NSDictionary structure.
	@discussion		Returns the key-value pairs defining the currently-selected export presets for a Version export. Returns nil if the user is exporting a Master image. 
*/
- (NSDictionary *)selectedExportPresetDictionary;


/*!	
	@abstract		Adds keywords to a Version image.
	@param			keywords An NSArray of NSString objects representing the keywords to add.
	@param			index The index of the target image.
	@discussion		This method has no effect if called on a Master image.
*/
- (void)addKeywords:(NSArray *)keywords 
	 toImageAtIndex:(unsigned)index;


/*!	 
	 @abstract		Adds keyword hierarchies to a Version image.
	 @param			hierarchicalKeywords This is an NSArray of NSArray objects, each containing NSString objects representing the hierarchy of a single keyword. For each NSArray, the NSString at index 0 is the keyword, with the item at index 1 being its parent, and so on.
	 @param			index The index of the target image.
	 @discussion	This method has no effect if called on a Master image.
	 */
- (void)addHierarchicalKeywords:(NSArray *)hierarchicalKeywords
				 toImageAtIndex:(unsigned)index;

/*!	
	@abstract		Adds custom metadata to a Version image.
	@param			customMetadata	An NSDictionary containing NSString key-value pairs representing the custom metadata.
	@param			index The index of the target image.
	@discussion		This method has no effect if called on a Master image.
*/
- (void)addCustomMetadataKeyValues:(NSDictionary *)customMetadata 
					toImageAtIndex:(unsigned)index;


/*!	
	@abstract		Provides reference to frontmost window.
	@result			A reference to the current frontmost window.
	@discussion		Until the plug-in calls -shouldBeginExport, the reference points to the export window. After the export process begins, the reference points to the progress sheet or to Aperture's main window.
*/
- (id)window;


/*!	
	@abstract		Indicates whether Aperture is exporting Master or Version images.
	@result			Returns YES if Aperture is exporting Master images. Returns NO if Aperture is exporting Version images.
*/
- (BOOL)isMasterExport;


/*!	
	@abstract		Tells Aperture to start the export process.
	@discussion		Calling this method causes Aperture to determine the destination path for export, confirm the images to export, put away the export window, and begin the export process. A plug-in should call this method only in response to -exportManagerShouldBeginExport and after performing any necessary validations, network checks, and so on.
*/
- (void)shouldBeginExport;


/*!	
	@abstract		Tells Aperture to cancel the export process.
	@discussion		The plug-in can call this method at any time to have Aperture put away all export windows, stop the export process, and return the user to the workspace. Additionally, if Aperture calls -exportManagerShouldCancelExport, Aperture then halts all activity and waits for the plug-in to call this method.
*/
- (void)shouldCancelExport;


/*!	
	@abstract		Signals that Aperture can deallocate the plug-in.
	@discussion		When Aperture finishes processing the export image data, it calls -exportManagerDidFinishExport. It continues to ask the plug-in for progress updates until the plug-in calls this method. Once this happens, Aperture closes the export modal window and deallocates the plug-in. 
*/
- (void)shouldFinishExport;


@end