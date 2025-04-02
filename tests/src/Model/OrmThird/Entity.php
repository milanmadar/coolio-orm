<?php

namespace tests\Model\OrmThird;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    /**
     * 
     * @param string|null $val
     * @return $this
     */
    public function setFkToThis(?string $val): self
    {
        $this->_set('fk_to_this', $val);
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getFkToThis(): ?string
    {
        return $this->_get('fk_to_this');
    }

}