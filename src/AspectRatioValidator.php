<?php

namespace Sitelease\AspectRatioValidator;

use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Core\Config\Configurable;
use Sitelease\AspectRatioValidator\AspectRatioValidatorFailedException;

class AspectRatioValidator extends Upload_Validator
{
    use Configurable;

    /**
     * Stores the aspect ratios that an image
     * can have to be considered valid
     *
     * @var array
     */
    public array $allowedAspectRatios = [];

    public function __construct(array $allowedAspectRatios = [])
    {
        $this->allowedAspectRatios = $allowedAspectRatios;
    }

    /**
     * Returns an array containing information about the file
     * (isImage => bool, width => int|null, and height => int|null)
     *
     * @param string $filename
     * @return array
     */
    public function getFileData(string $filename): array
    {
        $imageDetails = getimagesize($filename);

        if ($imageDetails === false) {
            return [
                'isImage' => false,
                'width'   => null,
                'height'  => null,
            ];
        } else {
            return [
                'isImage' => true,
                'width'   => $imageDetails[0],
                'height'  => $imageDetails[1],
            ];
        }
    }

    /**
     * Accepts an image's height and width and returns
     * a reduced aspect ratio
     *
     * @return string Aspect ratio (e.g. 500px by 500px would return "1x1")
     */
    public function getAspectRatio(int $width, int $height): string
    {
        $greatestCommonDivisor = static function ($width, $height) use (&$greatestCommonDivisor) {
            return ($width % $height) ? $greatestCommonDivisor($height, $width % $height) : $height;
        };

        $divisor = $greatestCommonDivisor($width, $height);

        return $width / $divisor . 'x' . $height / $divisor;
    }

    /**
     * Returns true if the uploaded image has a valid aspect ratio.
     * Otherwise throws a validation error
     *
     * @return bool
     * @throws AspectRatioValidatorFailedException
     */
    public function aspectRatioIsValid(): bool
    {
        $allowedAspectRatios = $this->allowedAspectRatios;
        $imageDetails = $this->getFileData($this->tmpFile['tmp_name']);

        if ($imageDetails['isImage'] === false) {
            throw new AspectRatioValidatorFailedException(
                _t(__CLASS__.'.NotAnImage', 'File is not an image')
            );
        }

        $aspectRatio = $this->getAspectRatio($imageDetails['width'], $imageDetails['height']);
        if (!in_array($aspectRatio, $allowedAspectRatios)) {
            throw new AspectRatioValidatorFailedException(
                _t(__CLASS__.'.InvalidAspectRatio', 'Image has an aspect ratio of {aspectRatio} which is not a valid aspect ratio ({validRatios})', [
                    'aspectRatio' => $aspectRatio,
                    'validRatios' => join(', ', $allowedAspectRatios)
                ])
            );
        }

        return true;
    }

    /**
     * Returns true if the uploaded image or file
     * has a valid aspect ratio
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (parent::validate() === false) {
            return false;
        }

        try {
            return $this->aspectRatioIsValid();
        } catch (AspectRatioValidatorFailedException $e) {
            $this->errors[] = _t(__CLASS__.'.ErrorPrefix', 'Error: {error}', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
