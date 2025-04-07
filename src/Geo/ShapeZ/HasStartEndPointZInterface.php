<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

interface HasStartEndPointZInterface
{
    public function getStartPointZ(): PointZ;
    public function getEndPointZ(): PointZ;
}