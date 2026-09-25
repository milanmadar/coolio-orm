<?php

namespace Milanmadar\CoolioORM\DoctrineDBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class IntArrayBracketsType extends Type
{
    const NAME = 'int[]';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // return the SQL used to create your column type. To create a portable column type, use the $platform.
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if(!isset($value)) {
            return null;
        }

        if($value === '{}') {
            return [];
        }

        // Handle postgres array string '{1,2,NULL,4}'
        $trimmed = trim((string)$value, '{}');
        if ($trimmed === '') {
            return [];
        }

        $items = explode(',', $trimmed);
        $result = [];

        foreach ($items as $item) {
            // Postgres represents NULL inside arrays as unquoted NULL
            if ($item === 'NULL') {
                $result[] = null;
            } else {
                $result[] = (int)$item;
            }
        }

        return $result;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (!isset($value)) {
            return null;
        }

        if (is_array($value)) {
            return '{' . implode(',', array_map('intval', $value)) . '}';
        }

        return (string)$value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}