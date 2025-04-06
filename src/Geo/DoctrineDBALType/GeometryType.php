<?php

namespace Milanmadar\CoolioORM\Geo\DoctrineDBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class GeometryType extends Type
{
    const NAME = 'geometry'; // modify to match your type name

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // return the SQL used to create your column type. To create a portable column type, use the $platform.
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // This is executed when the value is read from the database. Make your conversions here, optionally using the $platform.
        return (string)$value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
        return (string)$value;
    }

    public function getName()
    {
        return self::NAME;
    }
}