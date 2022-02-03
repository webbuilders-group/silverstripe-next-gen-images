<?php
namespace WebbuildersGroup\NextGenImages\Shortcodes;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider as SS_ImageShortcodeProvider;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\HTML;

class ImageShortcodeProvider extends SS_ImageShortcodeProvider
{
    /**
     * Replace"[image id=n]" shortcode with an image reference. Permission checks will be enforced by the file routing itself.
     * @param array $args Arguments passed to the parser
     * @param string $content Raw shortcode
     * @param ShortcodeParser $parser Parser
     * @param string $shortcode Name of shortcode used to register this handler
     * @param array $extra Extra arguments
     * @return string Result of the handled shortcode
     */
    public static function handle_shortcode($args, $content, $parser, $shortcode, $extra = [])
    {
        $allowSessionGrant = static::config()->allow_session_grant;

        $cache = static::getCache();
        $cacheKey = static::getCacheKey($args);

        $item = $cache->get($cacheKey);
        if ($item) {
            // Initiate a protected asset grant if necessary
            if (!empty($item['filename']) && $allowSessionGrant) {
                Injector::inst()->get(AssetStore::class)->grant($item['filename'], $item['hash']);
            }

            return $item['markup'];
        }

        // Find appropriate record, with fallback for error handlers
        $fileFound = true;
        /** @var null|\SilverStripe\Assets\Storage\AssetContainer|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $record **/
        $record = static::find_shortcode_record($args, $errorCode);
        if ($errorCode) {
            $fileFound = false;
            $record = static::find_error_record($errorCode);
        }

        if (!$record) {
            return null; // There were no suitable matches at all.
        }

        // Check if a resize is required
        $width = null;
        $height = null;
        $src = $record->getURL($allowSessionGrant);
        if ($record instanceof Image) {
            $width = isset($args['width']) ? (int) $args['width'] : null;
            $height = isset($args['height']) ? (int) $args['height'] : null;
            $hasCustomDimensions = ($width && $height);
            if ($hasCustomDimensions && (($width != $record->getWidth()) || ($height != $record->getHeight()))) {
                /** @var \SilverStripe\Assets\Storage\AssetContainer|\WebbuildersGroup\NextGenImages\Extensions\ImageExtension $resized **/
                $resized = $record->ResizedImage($width, $height);
                // Make sure that the resized image actually returns an image
                if ($resized) {
                    $src = $resized->getURL($allowSessionGrant);
                }
            }
        }

        // Determine whether loading="lazy" is set
        $args = self::updateLoadingValue($args, $width, $height);

        // Build the HTML tag
        $imgAttrs = array_merge(
            // Set overrideable defaults ('alt' must be present regardless of contents)
            ['src' => '', 'alt' => ''],
            // Use all other shortcode arguments
            $args,
            // But enforce some values
            ['id' => '', 'src' => $src]
        );

        // If file was not found then use the Title value from static::find_error_record() for the alt attr
        if (!$fileFound) {
            $imgAttrs['alt'] = $record->Title;
        }

        // Clean out any empty attributes (aside from alt)
        $imgAttrs = array_filter($imgAttrs, function ($k, $v) {
            return strlen(trim($v)) || $k === 'alt';
        }, ARRAY_FILTER_USE_BOTH);

        if (!$record->getIsWebP() && $record->exists()) {
            $markup = HTML::createTag(
                'picture',
                [],
                HTML::createTag(
                    'source',
                    [
                        'srcset' => ($resized ? $resized->getWebP()->getURL($allowSessionGrant) : $record->getWebP()->getURL($allowSessionGrant)),
                        'type' => 'image/webp',
                    ]
                ) .
                HTML::createTag('img', $imgAttrs)
            );
        } else {
            $markup = HTML::createTag('img', $imgAttrs);
        }

        // cache it for future reference
        if ($fileFound) {
            $cache->set($cacheKey, [
                'markup' => $markup,
                'filename' => ($record instanceof File ? $record->getFilename() : null),
                'hash' => ($record instanceof File ? $record->getHash() : null),
            ]);
        }

        return $markup;
    }

    /**
     * Updated the loading attribute which is used to either lazy-load or eager-load images Eager-load is the default browser behaviour so when eager loading is specified, the loading attribute is omitted
     * @param array $args
     * @param int|null $width
     * @param int|null $height
     * @return array
     */
    private static function updateLoadingValue(array $args, ?int $width, ?int $height): array
    {
        if (!Image::getLazyLoadingEnabled()) {
            return $args;
        }

        if (isset($args['loading']) && $args['loading'] == 'eager') {
            // per image override - unset the loading attribute unset to eager load (default browser behaviour)
            unset($args['loading']);
        } else if ($width && $height) {
            // width and height must be present to prevent content shifting
            $args['loading'] = 'lazy';
        }

        return $args;
    }
}
