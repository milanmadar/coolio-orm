<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

interface HasStartEndPointInterface
{
    public function getStartPoint(): Point;
    public function getEndPoint(): Point;
}