<?php
namespace WebbuildersGroup\NextGenImages;

use SilverStripe\Assets\InterventionBackend as SS_InterventionBackend;
use SilverStripe\Assets\Storage\AssetStore;

class NextGenImagesBackend extends SS_InterventionBackend
{
    /**
     * Write to the given asset store
     * @param AssetStore $assetStore
     * @param string $filename Name for the resulting file
     * @param string $hash Hash of original file, if storing a variant.
     * @param string $variant Name of variant, if storing a variant.
     * @param array $config Write options. {@see AssetStore}
     * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash will be calculated from the given data.
     * @throws BadMethodCallException If image isn't valid
     */
    public function writeToStore(AssetStore $assetStore, $filename, $hash = null, $variant = null, $config = [])
    {
        $result = parent::writeToStore($assetStore, $filename, $hash, $variant, $config);
        if ($result) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $resource = $this->getImageResource();
            $webpResult = $assetStore->setFromString(
                $resource->encode('webp', $this->getQuality())->getEncoded(),
                $filename . '.webp',
                $hash,
                $variant,
                $config
            );

            // Warm cache for the result
            if ($webpResult) {
                $this->warmCache($webpResult['Hash'], $webpResult['Variant']);
            }
        }

        return $result;
    }
}
