<?php

namespace tests\Model\OrmJsonTest;

use Milanmadar\CoolioORM;

class Entity extends CoolioORM\Entity
{
    /**
     * 
     * @param array<string|int, mixed>|null $val
     * @return $this
     */
    public function setFldJsonb(?array $val): self
    {
        $this->_set('fld_jsonb', $val);
        return $this;
    }

    /**
     * 
     * @return array<string|int, mixed>|null
     */
    public function getFldJsonb(): ?array
    {
        return $this->_get('fld_jsonb');
    }

    /**
     * 
     * @param array<string|int, mixed>|null $val
     * @return $this
     */
    public function setFldJson(?array $val): self
    {
        $this->_set('fld_json', $val);
        return $this;
    }

    /**
     * 
     * @return array<string|int, mixed>|null
     */
    public function getFldJson(): ?array
    {
        return $this->_get('fld_json');
    }

}