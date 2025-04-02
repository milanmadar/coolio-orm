<?php

namespace tests\Model\OrmOther;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    /**
     * 
     * @param int $val
     * @return $this
     */
    public function setFldInt(int $val): self
    {
        $this->_set('fld_int', $val);
        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getFldInt(): ?int
    {
        return $this->_get('fld_int');
    }

    /**
     * 
     * @param string|null $val
     * @return $this
     */
    public function setTitle(?string $val): self
    {
        $this->_set('title', $val);
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->_get('title');
    }

}