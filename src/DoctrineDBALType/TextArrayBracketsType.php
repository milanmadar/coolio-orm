<?php

namespace Milanmadar\CoolioORM\DoctrineDBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TextArrayBracketsType extends Type
{
    const NAME = 'text[]';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // return the SQL used to create your column type. To create a portable column type, use the $platform.
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // This is executed when the value is read from the database. Make your conversions here, optionally using the $platform.
        //return $value === '{}' ? [] : str_getcsv(trim($value, '{}'), ',', '"', '\\');
        //return $value === '{}' ? [] : str_getcsv(trim($value, '{}'));
        //return $value === '{}' ? [] : explode(',', substr($value, 1, -1));

        if(!isset($value)) {
            return null;
        }

        if($value === '{}') {
            return [];
        }

        $csv = str_getcsv(trim($value, '{}'), ',', '"', '\\');

        // str_getcsv() doesn’t unescape \" to ", and \\ to \, even with '\\' as escape—it just strips one layer.
        if(str_contains($value, '\\')) {
            $slashless = [];
            foreach($csv as $v) {
                $slashless[] = stripslashes((string)$v);
            }
            return $slashless;
        }

        return $csv;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
        $escapedItems = array_map(function ($item) {
            // Escape backslashes and double quotes
            $item = str_replace(['\\', '"'], ['\\\\', '\\"'], $item);
            return "\"$item\"";
        }, $value);

        return '{' . implode(',', $escapedItems) . '}';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}