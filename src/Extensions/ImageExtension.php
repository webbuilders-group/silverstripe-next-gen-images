<?php
namespace WebbuildersGroup\NextGenImages\Extensions;

use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Extension;

/**
 * Class \WebbuildersGroup\NextGenImages\Extensions\ImageExtension
 *
 * @property \SilverStripe\Assets\Image|\SilverStripe\Assets\Storage\DBFile|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $owner
 */
class ImageExtension extends Extension
{
    /**
     * Detects if a file is a WebP image or not
     * @return bool
     */
    public function getIsWebP()
    {
        return (preg_match('/\.webp$/', $this->owner->getFilename()) || $this->owner->getMimeType() == 'image/webp');
    }

    /**
     * Converts the current image to WebP
     * @return \SilverStripe\Assets\Storage\DBFile
     */
    public function getWebP()
    {
        // Do nothing we aleady appear to have a webp
        if ($this->owner->getIsWebP()) {
            return $this->owner;
        }

        $original = $this->owner;
        return $original->manipulateExtension(
            'webp',
            function (AssetStore $store, string $filename, string $hash, string $variant) use ($original) {
                $backend = $original->getImageBackend();
                $config = ['conflict' => AssetStore::CONFLICT_USE_EXISTING];
                $tuple = $backend->writeToStore($store, $filename, $hash, $variant, $config);
                return [$tuple, $backend];
            }
        );
    }
}
