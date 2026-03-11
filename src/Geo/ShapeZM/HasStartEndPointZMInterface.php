<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

interface HasStartEndPointZMInterface
{
    public function getStartPointZM(): PointZM;
    public function getEndPointZM(): PointZM;
}