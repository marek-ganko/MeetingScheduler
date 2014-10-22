<?php

/**
 * Class Scheduler
 */
class Scheduler
{

    const INTERVAL = 30;

    const DATE_FORMAT = 'Y-m-d H:i';

    /**
     * @var DateTimeZone
     */
    private $currentTimezone;

    /**
     * @var array
     */
    private $available = [];

    /**
     * @var array
     */
    private $unavailable = [];

    public function __construct()
    {
        $this->setDefaultTimezone();
    }

    /**
     * Sets local timezone
     * @return $this
     */
    private function setDefaultTimezone()
    {
        $this->currentTimezone = new DateTimeZone(date_default_timezone_get());
        return $this;
    }

    /**
     * @param DateTimeZone $currentTimezone
     * @return $this
     */
    public function setCurrentTimezone($currentTimezone)
    {
        $this->currentTimezone = $currentTimezone;
        return $this;
    }

    /**
     * Returns available time-slots for attendees in specified time frame
     *
     * @param AttendeeList $attendeeList
     * @param int $meetingLength - in minutes
     * @param int $maxTimeSlots
     * @param string $timeFrameStart
     * @param string $timeFrameEnd
     * @return array
     */
    public function findAvailableTimeSlots(
        AttendeeList $attendeeList,
        $meetingLength,
        $maxTimeSlots,
        $timeFrameStart,
        $timeFrameEnd
    ) {

        $this->validateInputParams($meetingLength, $maxTimeSlots, $timeFrameStart, $timeFrameEnd);

        $dateRange = new DatePeriod(
            new DateTime($timeFrameStart, $this->currentTimezone),
            new DateInterval('PT' . min(self::INTERVAL, $meetingLength) . 'M'),
            new DateTime($timeFrameEnd, $this->currentTimezone)
        );

        foreach ($attendeeList as $attendee) {

            $timeSlots = $this->getTimeSlotsFromCache() ?: $dateRange;

            foreach ($timeSlots as $value) {
                $this->setTimeSlot(is_array($value) ? $value['timeSlot'] : $value, $meetingLength, $attendee);
            }

        }

        return $this->getOutput($maxTimeSlots);
    }

    /**
     * @param int $meetingLength
     * @param int $maxTimeSlots
     * @param string $timeFrameStart
     * @param string $timeFrameEnd
     * @throws InvalidArgumentException
     */
    private function validateInputParams($meetingLength, $maxTimeSlots, $timeFrameStart, $timeFrameEnd)
    {
        if ($timeFrameStart > $timeFrameEnd) {
            throw new InvalidArgumentException('Start date is older than End date');
        }

        if ($maxTimeSlots < 1) {
            throw new InvalidArgumentException('There must be minimum 1 time-slot');
        }

        if ($meetingLength < 1) {
            throw new InvalidArgumentException('Meeting has to last for at least 1 minute');
        }
    }

    /**
     * Returns time-slots from available array or unavailable in case of not finding the available one
     *
     * @return array|null
     */
    private function getTimeSlotsFromCache()
    {
        if (empty($this->available) && !empty($this->unavailable)) {
            return $this->unavailable;
        }
        if (!empty($this->available)) {
            return $this->available;
        }

        return null;
    }

    /**
     * Sets time-slot
     *
     * @param DateTime $startTime
     * @param int $meetingLength
     * @param Attendee $attendee
     */
    private function setTimeSlot(DateTime $startTime, $meetingLength, Attendee $attendee)
    {
        $startTimeString = $startTime->format(self::DATE_FORMAT);

        if (!isset($this->unavailable[$startTimeString]) && !isset($this->available[$startTimeString])) {
            $this->available[$startTimeString] = [
                'timeSlot' => $startTime,
                'available' => [],
                'unavailable' => [],
                'participants' => 0
            ];
        }

        if (!$this->attendeeIsAvailableForTimeSlot($startTime, $meetingLength, $attendee)) {
            if (!isset($this->unavailable[$startTimeString])) {
                $this->unavailable[$startTimeString] = $this->available[$startTimeString];
                unset($this->available[$startTimeString]);
            }

            $this->unavailable[$startTimeString]['unavailable'][] = & $attendee;
        } else {
            if (isset($this->unavailable[$startTimeString])) {
                $this->unavailable[$startTimeString]['available'][] = & $attendee;
                $this->unavailable[$startTimeString]['participants']++;
            } else {
                $this->available[$startTimeString]['available'][] = & $attendee;
                $this->available[$startTimeString]['participants']++;
            }
        }
    }

    /**
     * Check if given meeting is available for attendee
     *
     * @param DateTime $startTime
     * @param int $meetingLength
     * @param Attendee $attendee
     * @return bool
     */
    private function attendeeIsAvailableForTimeSlot(DateTime $startTime, $meetingLength, Attendee $attendee)
    {
        if ($this->currentTimezone->getName() !== $attendee->getCurrentTimezone()->getName()) {
            $attendee->setCurrentTimezone($this->currentTimezone);
        }

        $endTime = clone $startTime;
        $endTime->add(new DateInterval('PT' . $meetingLength . 'M'));

        $workStart = new DateTime(
            $startTime->format('Y-m-d') . ' ' . $attendee->getWorkingHours()['from'],
            $attendee->getCurrentTimezone()
        );

        $workEnd = new DateTime(
            $startTime->format('Y-m-d') . ' ' . $attendee->getWorkingHours()['to'],
            $attendee->getCurrentTimezone()
        );

        if ($startTime >= $workStart && $endTime <= $workEnd) {

            /** @var DateTime[] $timeSlot */
            foreach ($attendee->getBookedTimeSlots() as $timeSlot) {

                $bookedStart = $timeSlot['from'];
                $bookedEnd = $timeSlot['to'];

                if ($this->timeSlotIsBooked($startTime, $endTime, $bookedStart, $bookedEnd)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Returns results formatted for output
     *
     * @param int $maxTimeSlots
     * @return array
     */
    private function getOutput($maxTimeSlots)
    {
        $output = ['message' => null, 'data' => null];

        if (empty($this->available)) {

            $output['message'] = 'It\'s not possible to arrange meeting with everyone';
            $output['data'] = $this->getTimeSlotWithMaxParticipants();
            if ($output['data']['participants'] == 0) {
                $output['message'] = 'It\'s not possible to arrange meeting for anyone';
                $output['data'] = null;
            }
        } else {

            while ($maxTimeSlots && !empty($this->available)) {
                $output['data'][] = array_shift($this->available)['timeSlot'];
                --$maxTimeSlots;
            }

        }

        return $output;
    }

    /**
     * Return time-slot with maximum number of participants
     *
     * @return array
     */
    private function getTimeSlotWithMaxParticipants()
    {
        $timeSlot = [];
        foreach ($this->unavailable as $value) {
            if (empty($timeSlot) || $value['participants'] > $timeSlot['participants']) {
                $timeSlot = $value;
            }
        }
        return $timeSlot;
    }

    /**
     * Check if given time-slot intersects with bookend time-slot
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @param DateTime $bookedStart
     * @param DateTime $bookedEnd
     * @return bool
     */
    private function timeSlotIsBooked(
        DateTime $startTime,
        DateTime $endTime,
        DateTime $bookedStart,
        DateTime $bookedEnd
    ) {
        return ($bookedStart >= $startTime && $bookedStart < $endTime) ||
        ($bookedEnd > $startTime && $bookedEnd <= $endTime) ||
        ($startTime >= $bookedStart && $startTime < $bookedEnd) ||
        ($endTime > $bookedStart && $endTime <= $bookedEnd);
    }
}