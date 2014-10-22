<?php

/**
 * Class AttendeeList
 */
class AttendeeList implements Iterator, ArrayAccess
{

    /**
     * @var Attendee[]
     */
    private $items = [];

    /**
     * @param array $items
     */
    public function __construct(array $items = array())
    {
        $this->items = $items;
    }

    /**
     * Fill Attendee list from json string
     *
     * @param string $json
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setFromJson($json)
    {
        $array = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        $this->setFromArray($array);
        return $this;
    }

    /**
     * Fill Attendee list from array
     *
     * @param array $attendees
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setFromArray(array $attendees)
    {
        foreach ($attendees as $attendee) {
            $attendee['booked'] = !empty($attendee['booked']) ? $attendee['booked'] : [];

            if (empty($attendee['name']) || empty($attendee['timezone']) || empty($attendee['work'])) {
                throw new InvalidArgumentException('Wrong attendee params');
            }

            $this->offsetSet(
                null,
                new Attendee($attendee['name'], $attendee['timezone'], $attendee['work'], $attendee['booked'])
            );
        }

        return $this;
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return Attendee
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * @param mixed $offset
     * @param Attendee $value
     * @return $this
     */
    public function offsetSet($offset, $value)
    {
        if (isset($offset)) {
            $this->items[$offset] = $value;
        } else {
            $this->items[] = $value;
        }
        return $this;
    }

    /**
     * @param mixed $offset
     * @return $this
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->items[$offset]);
        }
        return $this;
    }

    /**
     * @return Attendee
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     *
     */
    public function next()
    {
        next($this->items);
    }

    /**
     * @return Attendee
     */
    public function key()
    {
        key($this->items);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->current() !== false;
    }

    /**
     *
     */
    public function rewind()
    {
        reset($this->items);
    }
}