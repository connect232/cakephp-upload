<?php
namespace Upload\Database\Type;

use Cake\Database\DriverInterface;
use Cake\Database\Type\BaseType;
use PDO;

class UploadedFileType extends BaseType
{
    public function toPHP($value, DriverInterface $driver)
    {
        return $value;
    }

    public function marshal($value)
    {
        return $value;
    }

    public function toDatabase($value, DriverInterface $driver)
    {
        return $value;
    }

    public function toStatement($value, DriverInterface $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }
}
