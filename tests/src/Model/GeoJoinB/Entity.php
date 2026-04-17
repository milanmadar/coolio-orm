<?php

namespace tests\Model\GeoJoinB;

use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getAId(): int
    {
        return $this->_get('a_id');
    }

    public function setAId(int $val): self
    {
        $this->_set('a_id', $val);
        return $this;
    }
    public function getFldNotInOther(): int
    {
        return $this->_get('fld_notinother');
    }

    public function setFldNotInOther(int $val): self
    {
        $this->_set('fld_notinother', $val);
        return $this;
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