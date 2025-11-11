<?php

namespace MadeHQ\Cloudinary\UserForms;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use MadeHQ\Cloudinary\UserForms\Controllers\FormAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\UserForms\Control\UserDefinedFormAdmin;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField as EditableFormFieldEditableFileField;

/**
 * @param string $UploadFolder
 */
class EditableFileField extends EditableFormFieldEditableFileField
{
    private static $table_name = 'CloudinaryEditableFileField';

    /**
     *
     */
    private static $hidden = false;

    private static $db = [
        'UploadFolder' => 'Varchar(255)',
    ];

    /**
     * @inheritdoc
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('FolderID', static::getUploadFolderField());
        return $fields;
    }

    public static function getUploadFolderField()
    {
        return TextField::create('UploadFolder')
            ->setDescription(static::getUploadPrefixDescription())
            ->setAttribute('placeholder', FormAdmin::getDefaultSubmissionFolder());
    }

    /**
     * @return string
     */
    public static function getUploadPrefixDescription()
    {
        $prefix = trim(UserDefinedFormAdmin::config()->get('form_submissions_folder'), '/');

        return ($prefix) ?
            _t(__CLASS__ . '.UPLOAD_PREFIX_DESCRIPTION', 'Prefix ({prefix})', [
                'prefix' => $prefix,
            ]) :
            '';
    }

    /**
     * Stores the file in Cloudinary and stores it
     *
     * @return string
     */
    public function getValueFromData()
    {
        $data = func_get_arg(0);
        $data = $data[$this->Name];

        if ($data['error'] !== UPLOAD_ERR_OK) {
            // No file uploaded
            return null;
        }

        if ($this->MaxFileSizeMB) {
            if ($data['size'] > ($this->MaxFileSizeMB * 1024 * 1024)) {
                throw ValidationException::create(
                    _t(
                        __CLASS__ . '.FILETOOLARGE',
                        'The uploaded file "{filename}" exceeds the maximum allowed size of {size}MB.',
                        [
                            'filename' => $data['name'],
                            'size' => $this->MaxFileSizeMB,
                        ]
                    )
                );
            }
        }

        $uploadDir = Controller::join_links(
            UserDefinedFormAdmin::config()->get('form_submissions_folder'),
            trim((string) $this->UploadFolder)
        );

        $fileName = sprintf(
            'form-%d_field-%d/%s',
            $this->Parent()->ID,
            $this->ID,
            $data['name']
        );

        $api = (new Cloudinary(Configuration::instance()))->uploadApi();
        $config = [
            'folder' => $uploadDir,
            'public_id' => $fileName,
            'resource_type' => 'raw',
            'type' => 'private',
        ];

        $newData = $api->upload(
            $data['tmp_name'],
            $config
        );

        return $newData['public_id'];
    }

    public function getSubmittedFormField()
    {
        return SubmittedFileField::create();
    }
}
