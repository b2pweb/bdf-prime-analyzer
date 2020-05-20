<?php

namespace AnalyzerTest;

use Bdf\Prime\Entity\Model;

/**
 * Class RelationEntity
 */
class RelationEntity extends Model
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $label;

    public function __construct(array $data = [])
    {
        $this->import($data);
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
     * @return RelationEntity
     */
    public function setKey(string $key): RelationEntity
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return RelationEntity
     */
    public function setLabel(string $label): RelationEntity
    {
        $this->label = $label;
        return $this;
    }
}