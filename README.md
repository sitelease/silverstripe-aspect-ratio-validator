# Silverstripe image aspect ratio validator

This extends the standard upload validator and provides additional checks on the aspect ratio of an uploaded image.

The validator must be applied directly to an UploadField with an array of valid aspect ratios, like so:

```php
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            UploadField::create('Image')->setValidator(AspectRatioValidator::create(['1x1'])),
        ]);

        return $fields;
    }
```

**Note:** the validator should only be added to upload fields where the underlying class is an `Image` or subclass thereof. Adding the validator to an upload field where files may be uploaded will cause any non-images to be rejected.
