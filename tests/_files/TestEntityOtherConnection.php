<?php

namespace AnalyzerTest;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;

/**
 * Class TestEntityOtherConnection
 */
class TestEntityOtherConnection extends Model
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $value;

    public function __construct(array $data = [])
    {
        $this->import($data);
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
     * @return TestEntityOtherConnection
     */
    public function setId(string $id): TestEntityOtherConnection
    {
        $this->id = $id;

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
     * @return TestEntityOtherConnection
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
