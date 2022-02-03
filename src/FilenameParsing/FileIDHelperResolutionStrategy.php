<?php
namespace WebbuildersGroup\NextGenImages\FilenameParsing;

use League\Flysystem\Filesystem;
use SilverStripe\Assets\FilenameParsing\FileIDHelperResolutionStrategy as SS_FileIDHelperResolutionStrategy;
use SilverStripe\Assets\FilenameParsing\ParsedFileID;

class FileIDHelperResolutionStrategy extends SS_FileIDHelperResolutionStrategy
{
    /**
     * Try to find a file ID for an existing file using the provided file tuple.
     * @param array|ParsedFileID $tuple
     * @param Filesystem $filesystem
     * @param boolean $strict Whether we should enforce a hash check on the file we find
     * @return ParsedFileID|null
     */
    public function searchForTuple($tuple, Filesystem $filesystem, $strict = true)
    {
        $webp = false;
        if ($tuple instanceof ParsedFileID) {
            if (preg_match('/(?<extension>(\..+)*)\.webp$/', $tuple->getFilename())) {
                $webp = true;
                $tuple = $tuple
                    ->setFilename(preg_replace('/(?<extension>(\..+)*)\.webp$/', '$1', $tuple->getFilename()));
            }
        } else if (is_array($tuple)) {
            if (preg_match('/(?<extension>(\..+)*)\.webp$/', $tuple['Filename'])) {
                $webp = true;
                $tuple['Filename'] = preg_replace('/(?<extension>(\..+)*)\.webp$/', '$1', $tuple['Filename']);
            }
        }

        $result = parent::searchForTuple($tuple, $filesystem, $strict);

        if ($webp == true && $result) {
            return new ParsedFileID(
                $result->getFilename() . '.webp',
                $result->getHash(),
                $result->getVariant(),
                $result->getFileID() . '.webp'
            );
        }

        return $result;
    }

    /**
     * Given a fileID string or a Parsed File ID, create a matching ParsedFileID without any variant.
     * @param string|ParsedFileID $fileID
     * @return ParsedFileID|null A ParsedFileID with the expected FileID of the original file or null if the provided $fileID could not be understood
     */
    public function stripVariant($fileID)
    {
        $fileID = parent::stripVariant($fileID);
        if ($fileID instanceof ParsedFileID && preg_match('/(?<extension>(\..+)*)\.webp$/', $fileID->getFileID())) {
            $fileID = $fileID
                ->setFileID(preg_replace('/(?<extension>(\..+)*)\.webp$/', '$1', $fileID->getFileID()))
                ->setFilename(preg_replace('/(?<extension>(\..+)*)\.webp$/', '$1', $fileID->getFilename()));
        }

        return $fileID;
    }
}
