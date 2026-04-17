<?php

namespace tests\Model\GeoJoinA;

use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getPointGeom(): Shape2D\Point
    {
        return $this->_get('point_geom');
    }

    public function setPointGeom(Shape2D\Point $val): self
    {
        $this->_set('point_geom', $val);
        return $this;
    }

    /**
     * A varchar 25
     * @param string|null $val
     * @return $this
     */
    public function setFldVarchar(?string $val): self
    {
        $this->_set('fld_varchar', $val);
        return $this;
    }

    /**
     * A varchar 25
     * @return string|null
     */
    public function getFldVarchar(): string|null
    {
        return $this->_get('fld_varchar');
    }

    /**
     *
     * @param \DateTimeImmutable $val
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $val): self
    {
        $this->_set('created_at', $val);
        return $this;
    }

    /**
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->_get('created_at');
    }

}