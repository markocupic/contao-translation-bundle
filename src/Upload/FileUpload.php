<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Upload;

class FileUpload
{
    private string $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Get the files from the global $_FILES array.
     */
    public function getFilesFromGlobal(string $strName): array
    {
        // The "multiple" attribute is not set
        if (!\is_array($_FILES[$strName]['name'])) {
            return [$_FILES[$strName]];
        }

        $arrFiles = [];
        $intCount = \count($_FILES[$strName]['name']);

        for ($i = 0; $i < $intCount; ++$i) {
            if (!$_FILES[$strName]['name'][$i]) {
                continue;
            }

            $arrFiles[] = [
                'name' => $_FILES[$strName]['name'][$i],
                'type' => $_FILES[$strName]['type'][$i],
                'tmp_name' => $_FILES[$strName]['tmp_name'][$i],
                'error' => $_FILES[$strName]['error'][$i],
                'size' => $_FILES[$strName]['size'][$i],
            ];
        }

        return $arrFiles;
    }

    public function moveUploadedFiles(array $arrFiles, string $uploadFolder): array
    {
        $arrUploadedFiles = [];

        // Move files to the system/temp folder
        foreach ($arrFiles as $arrFile) {
            $targetPath = $uploadFolder.'/'.$arrFile['name'];

            if (move_uploaded_file($arrFile['tmp_name'], $targetPath)) {
                $arrUploadedFiles[] = $targetPath;
            }
        }

        return $arrUploadedFiles;
    }
}
