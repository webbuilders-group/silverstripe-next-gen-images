<?php
namespace WebbuildersGroup\NextGenImages\FilenameParsing;

use SilverStripe\Assets\FilenameParsing\NaturalFileIDHelper as SS_NaturalFileIDHelper;
use SilverStripe\Assets\FilenameParsing\ParsedFileID;

class NaturalFileIDHelper extends SS_NaturalFileIDHelper
{
    /**
     * Clean up filename to remove constructs that might clash with the underlying path format of this FileIDHelper.
     * @param string $filename
     * @return string
     */
    public function cleanFilename($filename)
    {
        $finalFilename = parent::cleanFilename($filename);
        return preg_replace('/\.webp$/', '', $finalFilename);
    }

    /**
     * Get Filename, Variant and Hash from a fileID. If a FileID can not be parsed, returns `null`.
     * @param string $fileID
     * @return ParsedFileID|null
     */
    public function parseFileID($fileID)
    {
        $pattern = '#^(?<folder>([^/]+/)*)(?<basename>((?<!__)[^/.])+)(__(?<variant>[^.]+))?(?<extension>(\..+)*)\.webp$#';

        // not a valid file (or not a part of the filesystem)
        if (!preg_match($pattern, $fileID, $matches) || strpos($matches['folder'], '_resampled') !== false) {
            return null;
        }

        $filename = $matches['folder'] . $matches['basename'] . $matches['extension'] . '.webp';
        return new ParsedFileID(
            $filename,
            '',
            isset($matches['variant']) ? $matches['variant'] : '',
            $fileID
        );
    }

    /**
     * Determine if the provided fileID is a variant of `$parsedFileID`.
     * @param string $fileID
     * @param ParsedFileID $parsedFileID
     * @return boolean
     */
    public function isVariantOf($fileID, ParsedFileID $original)
    {
        $variant = $this->parseFileID($fileID);
        return $variant && preg_replace('/\.webp$/', '', $variant->getFilename()) == $original->getFilename();
    }
}
