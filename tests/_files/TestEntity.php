<?php

namespace AnalyzerTest;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;

/**
 * Class TestEntity
 */
class TestEntity extends Model implements InitializableInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var RelationEntity
     */
    protected $embeddedRelation;

    /**
     * @var RelationEntity
     */
    protected $relationEntity;

    public function __construct(array $data = [])
    {
        $this->initialize();
        $this->import($data);
    }

    public function initialize(): void
    {
        $this->embeddedRelation = new RelationEntity();
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return TestEntity
     */
    public function setId(string $id): TestEntity
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return TestEntity
     */
    public function setKey(string $key): TestEntity
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return TestEntity
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return RelationEntity
     */
    public function relationEntity(): RelationEntity
    {
        return $this->relationEntity;
    }

    /**
     * @param RelationEntity $relationEntity
     * @return TestEntity
     */
    public function setRelationEntity(RelationEntity $relationEntity): TestEntity
    {
        $this->relationEntity = $relationEntity;
        return $this;
    }

    /**
     * @return RelationEntity
     */
    public function embeddedRelation(): RelationEntity
    {
        return $this->embeddedRelation;
    }

    /**
     * @param RelationEntity $embeddedRelation
     * @return TestEntity
     */
    public function setEmbeddedRelation(RelationEntity $embeddedRelation): TestEntity
    {
        $this->embeddedRelation = $embeddedRelation;
        return $this;
    }
}
