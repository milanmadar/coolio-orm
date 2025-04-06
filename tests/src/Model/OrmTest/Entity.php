<?php

namespace tests\Model\OrmTest;

use tests\Model\OrmOther;
use tests\Model\OrmThird;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    protected function relations(): ?array { return [
        'orm_other_id' => [OrmOther\Manager::class, 'id'],
        'orm_third_key' => [OrmThird\Manager::class, 'fk_to_this'],
    ];}

    /**
     * An integer
     * @param int|null $val
     * @return $this
     */
    public function setFldInt(?int $val): self
    {
        $this->_set('fld_int', $val);
        return $this;
    }

    /**
     * An integer
     * @return int|null
     */
    public function getFldInt(): ?int
    {
        return $this->_get('fld_int');
    }

    /**
     * A tiny int
     * @param bool|null $val
     * @return $this
     */
    public function setFldTinyInt(?bool $val): self
    {
        $this->_set('fld_tiny_int', $val);
        return $this;
    }

    /**
     * A tiny int
     * @return bool|null
     */
    public function getFldTinyInt(): ?bool
    {
        return $this->_get('fld_tiny_int');
    }

    /**
     * A small int
     * @param int|null $val
     * @return $this
     */
    public function setFldSmallInt(?int $val): self
    {
        $this->_set('fld_small_int', $val);
        return $this;
    }

    /**
     * A small int
     * @return int|null
     */
    public function getFldSmallInt(): ?int
    {
        return $this->_get('fld_small_int');
    }

    /**
     * A medium int
     * @param int|null $val
     * @return $this
     */
    public function setFldMediumInt(?int $val): self
    {
        $this->_set('fld_medium_int', $val);
        return $this;
    }

    /**
     * A medium int
     * @return int|null
     */
    public function getFldMediumInt(): ?int
    {
        return $this->_get('fld_medium_int');
    }

    /**
     * A floating 8,2
     * @param float|null $val
     * @return $this
     */
    public function setFldFloat(?float $val): self
    {
        $this->_set('fld_float', $val);
        return $this;
    }

    /**
     * A floating 8,2
     * @return float|null
     */
    public function getFldFloat(): ?float
    {
        return $this->_get('fld_float');
    }

    /**
     * A double 8,2
     * @param float|null $val
     * @return $this
     */
    public function setFldDouble(?float $val): self
    {
        $this->_set('fld_double', $val);
        return $this;
    }

    /**
     * A double 8,2
     * @return float|null
     */
    public function getFldDouble(): ?float
    {
        return $this->_get('fld_double');
    }

    /**
     * A decimal 8,2
     * @param float|null $val
     * @return $this
     */
    public function setFldDecimal(?float $val): self
    {
        $this->_set('fld_decimal', $val);
        return $this;
    }

    /**
     * A decimal 8,2
     * @return float
     */
    public function getFldDecimal(): float
    {
        return $this->_get('fld_decimal');
    }

    /**
     * A char 8
     * @param string|null $val
     * @return $this
     */
    public function setFldChar(?string $val): self
    {
        $this->_set('fld_char', $val);
        return $this;
    }

    /**
     * A char 8
     * @return string
     */
    public function getFldChar(): string
    {
        return $this->_get('fld_char');
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
     * @return string
     */
    public function getFldVarchar(): string
    {
        return $this->_get('fld_varchar');
    }

    /**
     * A text
     * @param string|null $val
     * @return $this
     */
    public function setFldText(?string $val): self
    {
        $this->_set('fld_text', $val);
        return $this;
    }

    /**
     * A text
     * @return string
     */
    public function getFldText(): string
    {
        return $this->_get('fld_text');
    }

    /**
     * A meidum text
     * @param string|null $val
     * @return $this
     */
    public function setFldMediumText(?string $val): self
    {
        $this->_set('fld_medium_text', $val);
        return $this;
    }

    /**
     * A meidum text
     * @return string
     */
    public function getFldMediumText(): string
    {
        return $this->_get('fld_medium_text');
    }

    /**
     * Json data
     * @param array<string|int, mixed>|null $val
     * @return $this
     */
    public function setFldJson(?array $val): self
    {
        $this->_set('fld_json', $val);
        return $this;
    }

    /**
     * Json data
     * @return array<string|int, mixed>
     */
    public function getFldJson(): array
    {
        return $this->_get('fld_json');
    }

    /**
     * Sets the related \Model\OrmOther\Entity and synchronizes the 'orm_other_id' field
     * @param OrmOther\Entity|null $ormOther
     * @return $this
     */
    public function setOrmOther(?OrmOther\Entity $ormOther): self
    {
        $this->_relationSetEntity('orm_other_id', $ormOther); 
        return $this;
    }
    
    /**
     * Returns the related \Model\OrmOther\Entity (optized with cache and synchronized with the 'orm_other_id' field)
     * @return OrmOther\Entity|null
     */
    public function getOrmOther(): ?OrmOther\Entity
    {
        /** @var \OrmOther\Entity|null */
        return $this->_relationGetEntity('orm_other_id');
    }

    /**
     * Tells if there is a related \Model\OrmOther\Entity (optized with cache and synchronized with the 'orm_other_id' field)
     * @return bool
     */
    public function hasOrmOther(): bool
    {
        return $this->_relationHasEntity('orm_other_id');
    }
    
    /**
     * Sets the 'orm_other_id' field (the related $this->getOrmOther() will be automatically synchronized as needed)
     * @param int|null $id The ID of the related related \Model\OrmOther\Entity
     * @return $this
     */
    public function setOrmOtherId(?int $val): self
    {
        $this->_set('orm_other_id', $val);
        return $this;
    }
    
    /**
     * Returns the 'orm_other_id' field (synchronized from the $this->setOrmOther() if needed)
     * @return int|null
     */
    public function getOrmOtherId(): ?int
    {
        return $this->_get('orm_other_id');
    }
    
    /**
     * Sets the related \Model\OrmThird\Entity and synchronizes the 'orm_third_key' field
     * @param OrmThird\Entity|null $ormThird
     * @return $this
     */
    public function setOrmThird(?OrmThird\Entity $ormThird): self
    {
        $this->_relationSetEntity('orm_third_key', $ormThird); 
        return $this;
    }
    
    /**
     * Returns the related \Model\OrmThird\Entity (optized with cache and synchronized with the 'orm_third_key' field)
     * @return OrmThird\Entity|null
     */
    public function getOrmThird(): ?OrmThird\Entity
    {
        /** @var OrmThird\Entity|null */
        return $this->_relationGetEntity('orm_third_key');
    }

    /**
     * Tells if there is a related \Model\OrmThird\Entity (optized with cache and synchronized with the 'orm_third_key' field)
     * @return bool
     */
    public function hasOrmThird(): bool
    {
        return $this->_relationHasEntity('orm_third_key');
    }
    
    /**
     * Sets the 'orm_third_key' field (the related $this->getOrmThird() will be automatically synchronized as needed)
     * @param string|null $id The ID of the related related \Model\OrmThird\Entity
     * @return $this
     */
    public function setOrmThirdKey(?string $val): self
    {
        $this->_set('orm_third_key', $val);
        return $this;
    }
    
    /**
     * Returns the 'orm_third_key' field (synchronized from the $this->setOrmThird() if needed)
     * @return string|null
     */
    public function getOrmThirdKey(): ?string
    {
        return $this->_get('orm_third_key');
    }
    
}