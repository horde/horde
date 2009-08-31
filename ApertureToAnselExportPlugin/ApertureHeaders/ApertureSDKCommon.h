/*!
   @header         ApertureSDKCommon.h
   @copyright      2007-2008 Apple Inc.. All rights reserved.

*/

/*!
 @define			kExportKeyThumbnailImage
 @discussion		An NSImage object containing a reduced-size JPEG of the specified image. Note that values may be nil during export for this key for Master images, or for versions of unsupported master formats. 
 */
#define kExportKeyThumbnailImage @"kExportKeyThumbnailImage"


/*!
 @define			kExportKeyVersionName
 @discussion		An NSString specifying the version name of the selected image.
 */
#define kExportKeyVersionName @"kExportKeyVersionName"


/*!
 @define			kExportKeyProjectName
 @discussion		An NSString specifying the name of the project containing the image.
 */
#define kExportKeyProjectName @"kExportKeyProjectName"


/*!
 @define			kExportKeyEXIFProperties
 @discussion		An NSDictionary specifying the EXIF key-value pairs for the image.
 */
#define kExportKeyEXIFProperties @"kExportKeyEXIFProperties"


/*!
 @define			kExportKeyIPTCProperties
 @discussion		An NSDictionary specifying all the IPTC key-value pairs for the image.
 */
#define kExportKeyIPTCProperties @"kExportKeyIPTCProperties"


/*!
 @define			kExportKeyCustomProperties
 @discussion		An NSDictionary specifying all the Custom Metadata key-value pairs for the image.
 */
#define kExportKeyCustomProperties @"kExportKeyCustomProperties"


/*!
 @define			kExportKeyKeywords
 @discussion		An NSArray specifying an NSString for each keyword for this image.
 */
#define kExportKeyKeywords @"kExportKeyKeywords"


/*!
 @define			kExportKeyHierarchicalKeywords
 @discussion		(New in Aperture 1.5.1) An NSArray specifying hierarchical keywords. Each entry in the array represents a single keyword and is itself an NSArray of NSStrings. Each hierarchy array starts with the keyword itself at index 0, followed by its parent, and so on. 
 */
#define kExportKeyHierarchicalKeywords @"kExportKeyHierarchicalKeywords"


/*!
 @define			kExportKeyMainRating
 @discussion		An NSNumber specifying the rating for this image.
 */
#define kExportKeyMainRating @"kExportKeyMainRating"


/*!
 @define			kExportKeyXMPString
 @discussion		An NSString specifying the XMP data for the original master of this image.
 */
#define kExportKeyXMPString @"kExportKeyXMPString"


/*!
 @define			kExportKeyReferencedMasterPath
 @discussion		An NSString specifying the absolute path to the master image file. 
 */
#define kExportKeyReferencedMasterPath @"kExportKeyReferencedMasterPath"


/*!
 @define			kExportKeyMasterPath
 @discussion		An NSString specifying the absolute path to the master image file. (The same value as kExportKeyReferencedMasterPath) 
 */
#define kExportKeyMasterPath @"kExportKeyReferencedMasterPath"


/*!
 @define			kExportKeyUniqueID
 @discussion		An NSString specifying a unique identifier for specified image.
 */
#define kExportKeyUniqueID @"kExportKeyUniqueID"


/*!
 @define			kExportKeyImageSize
 @discussion		An NSValue object specifying an NSSize with the pixel dimensions of the specified image. For Version images, the pixel dimensions take all cropping, adjustments, and rotations into account. For Master images, the size is the original pixel dimensions of the image.
 */
#define kExportKeyImageSize @"kExportKeyImageSize"

/*!
 @define			kExportKeyImageHasAdjustments
 @discussion		(New in Aperture 2.0) An NSNumber object specifying a Boolean value. YES indicates that the user has applied at least one adjustment to this version besides the RAW decode; NO indicates that no adjustments have been applied.
 */
#define kExportKeyImageHasAdjustments @"kExportKeyImageHasAdjustments"


/*!
 @define			kExportKeyWhiteBalanceTemperature
 @discussion		(New in Aperture 2.0) An NSNumber object specifying the color temperature value determined by Aperture.
 */
#define kExportKeyWhiteBalanceTemperature @"kExportKeyWhiteBalanceTemperature"

/*!
 @define			kExportKeyWhiteBalanceTint
 @discussion		(New in Aperture 2.0) An NSNumber object specifying the color tint value determined by Aperture.
 */
#define kExportKeyWhiteBalanceTint @"kExportKeyWhiteBalanceTint"

/*!
 @define			kkExportKeyIsRAWImage
 @discussion		(New in Aperture 2.0) An NSNumber object specifying a Boolean value. YES indicates that this version is based off a RAW master file. NO indicates that the master file is not RAW.
 */
#define kExportKeyIsRAWImage @"kExportKeyIsRAWImage"

/* New in Aperture 1.5.1, Part of ApertureExportManager version 2 */
/*!
 */
typedef enum
{
	kExportThumbnailSizeThumbnail = 0,
	kExportThumbnailSizeMini,
	kExportThumbnailSizeTiny
} ApertureExportThumbnailSize;
