<?php
namespace WebbuildersGroup\NextGenImages\Extensions;

use Intervention\Image\AbstractDriver;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;
use LogicException;

/**
 * Class \WebbuildersGroup\NextGenImages\Extensions\ImageExtension
 *
 * @property \SilverStripe\Assets\Image|\SilverStripe\Assets\Storage\DBFile|\PictouCounty\Extensions\ImageExtension $owner
 */
class ImageExtension extends DataExtension
{
    /**
     * @TODO
     * @return \SilverStripe\Assets\Storage\DBFile
     */
    public function getWebP()
    {
        return $this->manipulate(
            $this->owner->getVariant(),
            function (AssetStore $store, $filename, $hash, $variant) {
                /** @var Image_Backend $backend */
                $backend = $this->owner->getImageBackend();

                // If backend isn't available
                if (!$backend || !$backend->getImageResource()) {
                    return null;
                }

                // Delegate to user manipulation
                if (preg_match('/\.webp$/', $this->owner->getFilename())) {
                    return null;
                }

                $resource = $backend->getImageResource();
                if (!($result = $backend->setImageResource($resource->encode('webp', $backend->getQuality())))) {
                    return null;
                }

                // Write from another container
                if ($result instanceof AssetContainer) {
                    try {
                        $tuple = $store->setFromStream($result->getStream(), $filename, $hash, $variant);
                        return [$tuple, $result];
                    } finally {
                        // Unload the Intervention Image resource so it can be garbaged collected
                        $res = $backend->setImageResource(null);
                        gc_collect_cycles();
                    }
                }

                // Write from modified backend
                if ($result instanceof Image_Backend) {
                    try {
                        /** @var Image_Backend $result */
                        $tuple = $result->writeToStore(
                            $store,
                            $filename,
                            $hash,
                            $variant,
                            [
                                'conflict' => AssetStore::CONFLICT_USE_EXISTING,
                                'visibility' => $this->owner->getVisibility(),
                            ]
                        );

                        return [$tuple, $result];
                    } finally {
                        // Unload the Intervention Image resource so it can be garbaged collected
                        $res = $backend->setImageResource(null);
                        gc_collect_cycles();
                    }
                }

                // Unknown result from callback
                throw new LogicException('Invalid manipulation result');
            }
        );
    }

    /**
     * Generate a new DBFile instance using the given callback if it hasn't been created yet, or
     * return the existing one if it has.
     *
     * @param string $variant name of the variant to create
     * @param callable $callback Callback which should return a new tuple as an array.
     * This callback will be passed the backend, filename, hash, and variant
     * This will not be called if the file does not
     * need to be created.
     * @return DBFile The manipulated file
     */
    protected function manipulate($variant, $callback)
    {
        // Verify this manipulation is applicable to this instance
        if (!$this->owner->exists()) {
            return null;
        }

        // Build output tuple
        $filename = $this->owner->getFilename() . '.webp';
        $hash = $this->owner->getHash();
        $existingVariant = $this->owner->getVariant();
        if ($existingVariant) {
            $variant = $existingVariant;
        }

        // Skip empty files (e.g. Folder does not have a hash)
        if (empty($filename) || empty($hash)) {
            return null;
        }

        // Create this asset in the store if it doesn't already exist,
        // otherwise use the existing variant
        $store = Injector::inst()->get(AssetStore::class);
        $tuple = $manipulationResult = null;
        if (!$store->exists($filename, $hash, $variant)) {
            // Circumvent generation of thumbnails if we only want to get existing ones
            if (!$this->owner->getAllowGeneration()) {
                return null;
            }

            $result = call_user_func($callback, $store, $filename, $hash, $variant);

            // Preserve backward compatibility
            if (isset($result['Filename'])) {
                $tuple = $result;
                Deprecation::notice(
                    '5.0',
                    'Closure passed to ImageManipulation::manipulate() should return null or a two-item array
                        containing a tuple and an image backend, i.e. [$tuple, $result]',
                    Deprecation::SCOPE_GLOBAL
                );
            } else {
                list($tuple, $manipulationResult) = $result;
            }
        } else {
            $tuple = [
                'Filename' => $filename,
                'Hash' => $hash,
                'Variant' => $variant
            ];
        }

        // Callback may fail to perform this manipulation (e.g. resize on text file)
        if (!$tuple) {
            return null;
        }

        // Store result in new DBFile instance
        /** @var DBFile $file */
        $file = DBField::create_field('DBFile', $tuple);

        // Pass the manipulated image backend down to the resampled image - this allows chained manipulations
        // without having to re-load the image resource from the manipulated file written to disk
        if ($manipulationResult instanceof Image_Backend) {
            $file->setImageBackend($manipulationResult);
        }

        // Copy our existing attributes to the new object
        $file->initAttributes($this->owner->getAttributes());

        return $file->setOriginal($this);
    }
}
