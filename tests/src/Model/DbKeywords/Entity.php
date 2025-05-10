<?php

namespace tests\Model\DbKeywords;

use Milanmadar\CoolioORM;

class Entity extends CoolioORM\Entity
{
    /**
     * 
     * @param string $val
     * @return $this
     */
    public function setNull(string $val): self
    {
        $this->_set('null', $val);
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getNull(): string
    {
        return $this->_get('null');
    }

    /**
     * 
     * @param string $val
     * @return $this
     */
    public function setClass(string $val): self
    {
        $this->_set('class', $val);
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getClass(): string
    {
        return $this->_get('class');
    }

    /**
     * 
     * @param int|null $val
     * @return $this
     */
    public function setInt(?int $val): self
    {
        $this->_set('int', $val);
        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getInt(): ?int
    {
        return $this->_get('int');
    }

}