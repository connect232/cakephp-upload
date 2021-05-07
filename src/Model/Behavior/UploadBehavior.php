<?php

namespace Upload\Model\Behavior;

use ArrayObject;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\RulesChecker;
use Cake\Utility\Text;
use Upload\Database\Type\UploadedFileType;

class UploadBehavior extends Behavior
{
    protected $fields;

    public function initialize(array $config): void
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
     * Unsets file data if no file has been uploaded.
     *
     * @param Cake\Event\Event $event
     * @param ArrayObject $data request data
     * @param ArrayObject $options
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($this->fields as $index => $field) {
            if (isset($data[$index])) {
                if (!$data[$index]->getClientFilename()) {
                    unset($data[$index]);
                }
            }
        }
    }

    /**
     * Check if a file has been uploaded on all creation of new entities.
     *
     * @param Cake\Event\Event $event
     * @param RulesChecker $rules table rules
     */
    public function buildRules(Event $event, RulesChecker $rules)
    {
        foreach ($this->fields as $index => $field) {
            $rules->addCreate(function ($entity, $options) use ($index) {
                    return (bool) $entity->$index->getClientFilename();
                },
                'fileAdded',
                [
                    'errorField' => $index,
                    'message' => 'Please add a file'
                ]
            );
        }
    }

    /**
     * Slug and timestamps filenames before adding to destination folder.
     * Deletes old files (edit only) if entity saves successfully.
     *
     * @param Cake\Event\Event $event
     * @param Cake\Datasource\EntityInterface $entity
     * @param ArrayObject $options
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        foreach ($this->fields as $index => $field) {
            if ($entity->isDirty($index)) {
                $file = $entity->$index;
                $fileInfo = pathinfo($file->getClientFilename());

                // slugify filename before appending timestamp to filename
                $entity->$index = Text::slug(strtolower($fileInfo['filename'])) . '-' . time() . '.' . $fileInfo['extension'];

                // add file to destination folder
                $file->moveTo(WWW_ROOT . $field['path'] . DS . $entity->$index);

                if (!$entity->isNew() && is_string($entity->getOriginal($index))) {
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
        if (is_file(WWW_ROOT . $path . DS . $filename)) {
            return unlink(WWW_ROOT . $path . DS . $filename);
        } else {
            return false;
        }
    }
}
