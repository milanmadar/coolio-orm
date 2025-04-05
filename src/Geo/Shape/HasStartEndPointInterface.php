<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

interface HasStartEndPointInterface
{
    public function getStartPoint(): Point;
    public function getEndPoint(): Point;
}