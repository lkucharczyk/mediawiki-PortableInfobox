<?php

namespace Wikia\PortableInfobox\Helpers;


class PortableInfoboxImagesHelper {
	const MAX_DESKTOP_THUMBNAIL_HEIGHT = 500;

	protected static $count = 0;

	/**
	 * extends image data
	 *
	 * @param array $data image data
	 * @param int $thumbnailFileWidth preferred thumbnail file width
	 * @param int $thumbnailImgTagWidth preferred thumbnail img tag width
	 * @return array|bool false on failure
	 */
	public function extendImageData( $data, $thumbnailFileWidth, $thumbnailImgTagWidth = null ) {
		global $wgPortableInfoboxCustomImageWidth;

		// title param is provided through reference in WikiaFileHelper::getFileFromTitle
		$title = $data['name'];
		$file = $this->getFileFromTitle( $title );

		if (
			!$file || !$file->exists() ||
			!in_array( $file->getMediaType(), [ MEDIATYPE_BITMAP, MEDIATYPE_DRAWING, MEDIATYPE_VIDEO ] )
		) {
			return false;
		}

		// we don't need failing thumbnail creation for videos
		if( $file->getMediaType() == MEDIATYPE_VIDEO ) {
			return array_merge( $data, [
				'ref' => ++self::$count
			] );
		}

		// get dimensions
		$originalWidth = $file->getWidth();
		// we need to have different thumbnail file dimensions to support (not to have pixelated images) wider infoboxes than default width
		$fileDimensions = $this->getThumbnailSizes( $thumbnailFileWidth, self::MAX_DESKTOP_THUMBNAIL_HEIGHT,
			$originalWidth, $file->getHeight() );
		$imgTagDimensions =
			empty( $thumbnailImgTagWidth )
				? $fileDimensions
				: $this->getThumbnailSizes( $thumbnailImgTagWidth,
				self::MAX_DESKTOP_THUMBNAIL_HEIGHT, $originalWidth, $file->getHeight() );

		// if custom and big enough, scale thumbnail size
		$ratio =
			!empty( $wgPortableInfoboxCustomImageWidth ) &&
			$originalWidth > $wgPortableInfoboxCustomImageWidth
				? $wgPortableInfoboxCustomImageWidth / $fileDimensions['width'] : 1;
		// get thumbnail
		$thumbnail = $file->transform( [
			'width' => round( $fileDimensions['width'] * $ratio ),
			'height' => round( $fileDimensions['height'] * $ratio ),
		] );
		$thumbnail2x = $file->transform( [
			'width' => round( $fileDimensions['width'] * $ratio * 2 ),
			'height' => round( $fileDimensions['height'] * $ratio * 2 ),
		] );
		if ( !$thumbnail || $thumbnail->isError() || !$thumbnail2x || $thumbnail2x->isError() ) {
			return false;
		}

		return array_merge( $data, [
			'ref' => ++self::$count,
			'height' => intval( $imgTagDimensions['height'] ),
			'width' => intval( $imgTagDimensions['width'] ),
			'thumbnail' => $thumbnail->getUrl(),
			'thumbnail2x' => $thumbnail2x->getUrl()
		] );
	}

	/**
	 * @param array $images
	 * @return array
	 */
	public function extendImageCollectionData( $images ) {
		$images = array_map(
			function ( $image, $index ) {
				$image['dataRef'] = $index;

				return $image;
			},
			$images,
			array_keys($images)
		);

		$images[0]['isFirst'] = true;
		return [
			'images' => $images
		];
	}

	/**
	 * Calculates image dimensions based on preferred width and max acceptable height
	 *
	 * @param int $preferredWidth
	 * @param int $maxHeight
	 * @param int $originalWidth
	 * @param int $originalHeight
	 * @return array [ 'width' => int, 'height' => int ]
	 */
	public function getThumbnailSizes( $preferredWidth, $maxHeight, $originalWidth, $originalHeight ) {
		if ( ( $originalHeight / $originalWidth ) > ( $maxHeight / $preferredWidth ) ) {
			$height = min( $maxHeight, $originalHeight );
			$width = min( $preferredWidth, $height * $originalWidth / $originalHeight );
		} else {
			$width = min( $preferredWidth, $originalWidth );
			$height = min( $maxHeight, $width * $originalHeight / $originalWidth );
		}

		return [ 'height' => round( $height ), 'width' => round( $width ) ];
	}

	/**
	 * return real width of the image.
	 * @param \Title $title
	 * @return int number
	 */
	public function getFileWidth( $title ) {
		$file = \WikiaFileHelper::getFileFromTitle( $title );

		if ( $file ) {
			return $file->getWidth();
		}
		return 0;
	}

	/**
	 * Separated for overriding in unit testing
	 * @param \Title|string $title
	 * @return \File file
	 */
	protected function getFileFromTitle( $title ) {
		return \WikiaFileHelper::getFileFromTitle( $title );
	}
}
