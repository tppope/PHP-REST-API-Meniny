<?php


class Country implements \JsonSerializable
{
    private string $code;
    private string $name;
    private array $names;
    private array $holidays;
    private array $memorables;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @param array $names
     */
    public function setNames(array $names): void
    {
        $this->names = $names;
    }

    /**
     * @return array
     */
    public function getHolidays(): array
    {
        return $this->holidays;
    }

    /**
     * @param array $holidays
     */
    public function setHolidays(array $holidays): void
    {
        $this->holidays = $holidays;
    }

    /**
     * @return array
     */
    public function getMemorables(): array
    {
        return $this->memorables;
    }

    /**
     * @param array $memorables
     */
    public function setMemorables(array $memorables): void
    {
        $this->memorables = $memorables;
    }

    public function isAllEmpty():bool{
        if (empty($this->code)&&empty($this->name)&&empty($this->names)&&empty($this->holidays)&&empty($this->memorables))
            return true;
        return false;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
