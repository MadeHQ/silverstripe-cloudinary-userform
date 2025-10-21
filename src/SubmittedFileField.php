<?php

namespace MadeHQ\Cloudinary\UserForms;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use SilverStripe\UserForms\Model\Submission\SubmittedFileField as SubmissionSubmittedFileField;

class SubmittedFileField extends SubmissionSubmittedFileField
{
    private static $table_name = 'SubmittedCloudinaryFileField';

    /**
     * @var Cloudinary
     */
    protected static $cloudinary_instance;

    /**
     * @inheritdoc
     */
    public function getLink($grant = null)
    {
        if (! $this->Value) {
            return null;
        }
        $config = [
            'resource_type' => 'raw',
            'attachment' => true,
        ];
        return static::cloudinary_instance()->uploadApi()->privateDownloadUrl($this->Value, '', $config);
    }

    /**
     * Parent class returns a `File` object, but we're storing Cloudinary public_id in Value
     *
     * @return $this
     */
    public function getUploadedFileFromDraft()
    {
        return $this;
    }

    /**
     * @return Cloudinary
     */
    public static function cloudinary_instance()
    {
        if (!static::$cloudinary_instance) {
            static::$cloudinary_instance = new Cloudinary(Configuration::instance());
        }
        return static::$cloudinary_instance;
    }
}
