<?php
namespace WebbuildersGroup\NextGenImages\FilenameParsing;

use SilverStripe\Assets\FilenameParsing\HashFileIDHelper as SS_HashFileIDHelper;
use SilverStripe\Assets\FilenameParsing\ParsedFileID;

class HashFileIDHelper extends SS_HashFileIDHelper
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
        $pattern = '#^(?<folder>([^/]+/)*)(?<hash>[a-f0-9]{10})/(?<basename>((?<!__)[^/.])+)(__(?<variant>[^.]+))?(?<extension>(\..+)*)\.webp$#';

        // not a valid file (or not a part of the filesystem)
        if (!preg_match($pattern, $fileID, $matches)) {
            return null;
        }

        $filename = $matches['folder'] . $matches['basename'] . $matches['extension'] . '.webp';
        return new ParsedFileID(
            $filename,
            $matches['hash'],
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
        return $variant &&
            preg_replace('/\.webp$/', '', $variant->getFilename()) == $original->getFilename() &&
            $variant->getHash() == $this->truncate($original->getHash());
    }

    /**
     * Truncate a hash to a predefined length
     * @param $hash
     * @return string
     */
    private function truncate($hash)
    {
        return substr($hash, 0, self::HASH_TRUNCATE_LENGTH);
    }
}
