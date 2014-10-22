<?php

/**
 * Class Attendee
 */
class Attendee
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * @var DateTimeZone
     */
    private $currentTimezone;

    /**
     * @var array
     */
    private $workingHours = [];

    /**
     * @var array
     */
    private $bookedTimeSlots = [];

    /**
     * @param string $name
     * @param string $timezone
     * @param array $workingHours
     * @param array $bookedTimeSlots
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct($name, $timezone, array $workingHours, array $bookedTimeSlots = array())
    {
        $this->validateInputParams($timezone, $workingHours);

        $this->name = $name;
        $this->timezone = new DateTimeZone($timezone);
        $this->currentTimezone = new DateTimeZone($timezone);
        $this->workingHours = $workingHours;
        foreach ($bookedTimeSlots as $timeSlot) {
            if (!empty($timeSlot['from']) && !empty($timeSlot['to'])) {
                $this->bookedTimeSlots[] = [
                    'from' => new DateTime($timeSlot['from'], $this->currentTimezone),
                    'to' => new DateTime($timeSlot['to'], $this->currentTimezone)
                ];
            }
        }
    }

    /**
     * @param $timezone
     * @param array $workingHours
     * @return array
     * @throws InvalidArgumentException
     */
    private function validateInputParams($timezone, array $workingHours)
    {
        if (empty($workingHours['from']) || empty($workingHours['to'])) {
            throw new InvalidArgumentException('Wrong working hours passed');
        }

        if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
            throw new InvalidArgumentException('Wrong timezone passed');
        }
    }

    /**
     * Change dates and working hours to new timezone
     *
     * @param DateTimeZone $timezone
     * @return $this
     */
    private function switchTimezone(DateTimeZone $timezone)
    {
        $workingHours = [
            'from' => (
            new DateTime(
                $this->getWorkingHours()['from'],
                $this->getCurrentTimezone()
            )
            )->setTimezone($timezone)->format('H:i'),
            'to' => (
            new DateTime(
                $this->getWorkingHours()['to'],
                $this->getCurrentTimezone()
            )
            )->setTimezone($timezone)->format('H:i'),
        ];

        $this->setWorkingHours($workingHours);

        $switchedTimeSlots = [];

        /** @var DateTime[] $timeSlot */
        foreach ($this->getBookedTimeSlots() as $timeSlot) {
            $switchedTimeSlots[] = [
                'from' => $timeSlot['from']->setTimezone($timezone),
                'to' => $timeSlot['to']->setTimezone($timezone)
            ];
        }

        $this->setBookedTimeSlots($switchedTimeSlots);

        return $this;
    }

    /**
     * @param DateTimeZone $timezone
     * @return $this
     */
    public function setCurrentTimezone(DateTimeZone $timezone)
    {
        $this->switchTimezone($timezone);
        $this->currentTimezone = $timezone;
        return $this;
    }

    /**
     * @return DateTimeZone
     */
    public function getCurrentTimezone()
    {
        return $this->currentTimezone;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getWorkingHours()
    {
        return $this->workingHours;
    }

    /**
     * @param array $workingHours
     * @return $this
     */
    public function setWorkingHours($workingHours)
    {
        $this->workingHours = $workingHours;
        return $this;
    }

    /**
     * @return array
     */
    public function getBookedTimeSlots()
    {
        return $this->bookedTimeSlots;
    }

    /**
     * @param array $bookedTimeSlots
     * @return $this
     */
    public function setBookedTimeSlots($bookedTimeSlots)
    {
        $this->bookedTimeSlots = $bookedTimeSlots;
        return $this;
    }
}