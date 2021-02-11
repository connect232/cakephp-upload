<?php

namespace Upload\Model\Behavior;

use ArrayObject;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Filesystem\File;
use Cake\ORM\Behavior;
use Cake\Utility\Text;
use Upload\Database\Type\UploadedFileType;

class UploadBehavior extends Behavior
{
    protected $fields;

    public function initialize(array $config)
    {
        $this->fields = $config;

        // Set column type for configured fields (UploadedFileType)
        Type::map('uploadedFile', UploadedFileType::class);
        $schema = $this->_table->getSchema();

        foreach ($this->fields as $index => $field) {
            $schema->setColumnType($index, 'uploadedFile');
        }

        $this->_table->setSchema($schema);
    }

    /**
     * Slug and timestamps filenames before adding to destination folder.
     *
     * @param Cake\Event\Event $event
     * @param ArrayObject $data request data
     * @param ArrayObject $options
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($this->fields as $index => $field) {
            if (!$data[$index]['tmp_name']) {
                unset($data[$index]);
            }
        }
    }

    /**
     * Deletes old files (edit only) if entity saves successfully, else deletes new files.
     *
     * @param Cake\Event\Event $event
     * @param Cake\Datasource\EntityInterface $entity
     * @param ArrayObject $options
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        // delete old file (edit only) if entity saves successfully, else delete new file
        foreach ($this->fields as $index => $field) {
            if ($entity->isDirty($index)) {
                $file = new File($entity->file['tmp_name']);
                $file->info = pathinfo($entity->file['name']);
                $file->name = strtolower($entity->file['name']);

                // slugify filename before appending timestamp to filename
                $entity->file = Text::slug($file->name()) . '-' . time() . '.' . $file->ext();

                // add file to destination folder
                $file->copy(WWW_ROOT . $field['path'] . DS . $entity->file);

                if (!$entity->isNew()) {
                    $this->deleteFile($field['path'], $entity->getOriginal($index));
                }
            }
        }
    }

    /**
     * Deletes files associated with the deleted entity.
     *
     * @param Cake\Event\Event $event
     * @param Cake\Datasource\EntityInterface $entity
     * @param ArrayObject $options
     */
    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        // delete files associated with the deleted entity
        foreach ($this->fields as $index => $field) {
            $this->deleteFile($field['path'], $entity->$index);
        }
    }

    /**
     * Deletes the file.
     *
     * @param string $path absolute path to file
     * @param string $filename name of file
     * @return bool Success
     */
    private function deleteFile(string $path, string $filename) {
        if ($filename) {
            $file = new File(WWW_ROOT . $path . DS . $filename);
            return $file->delete();
        } else {
            return false;
        }
    }
}
