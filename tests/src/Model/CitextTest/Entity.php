<?php

namespace tests\Model\CitextTest;

use Milanmadar\CoolioORM;

class Entity extends CoolioORM\Entity
{
    /**
     * 
     * @param string|null $val
     * @return $this
     */
    public function setCitxtCol(?string $val): self
    {
        $this->_set('citxt_col', $val);
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getCitxtCol(): ?string
    {
        return $this->_get('citxt_col');
    }

}