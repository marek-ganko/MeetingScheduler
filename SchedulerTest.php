<?php

/**
 * Class SchedulerTest
 */
class SchedulerTest
{

    public function __construct()
    {
        $this->setAssertion();
    }

    private function setAssertion()
    {
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(
            ASSERT_CALLBACK,
            function () {
                echo 'Assertion FAILED' . PHP_EOL;
            }
        );
    }

    private function assertEqual($value1, $value2)
    {
        assert($value1 === $value2);
    }

    public function testWrongTimeSlotNumber()
    {
        $this->benchmark(true);

        try {
            $timezone = new DateTimeZone('UTC');
            $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
                new AttendeeList([new Attendee('John Doe', 'UTC', ['from' => '7:00', 'to' => '8:00'])]),
                30,
                0,
                '2014-08-01 08:00',
                '2014-08-01 09:00'
            );
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $expectedResult = 'There must be minimum 1 time-slot';

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testWrongMeetingLength()
    {
        $this->benchmark(true);

        try {
            $timezone = new DateTimeZone('UTC');
            $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
                new AttendeeList([new Attendee('John Doe', 'UTC', ['from' => '7:00', 'to' => '8:00'])]),
                0,
                5,
                '2014-08-01 08:00',
                '2014-08-01 09:00'
            );
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $expectedResult = 'Meeting has to last for at least 1 minute';

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testWrongTimeInterval()
    {
        $this->benchmark(true);

        try {
            $timezone = new DateTimeZone('UTC');
            $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
                new AttendeeList([new Attendee('John Doe', 'UTC', ['from' => '7:00', 'to' => '8:00'])]),
                60,
                5,
                '2014-08-01 09:00',
                '2014-08-01 08:00'
            );
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $expectedResult = 'Start date is older than End date';

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testFindAvailableTimeSlotsMinimal()
    {
        $this->benchmark(true);

        $attendeeList = new AttendeeList(
            [
                new Attendee(
                    'John Doe', 'UTC', ['from' => '7:00', 'to' => '8:00']
                )
            ]
        );

        $timezone = new DateTimeZone('UTC');
        $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
            $attendeeList,
            60,
            5,
            '2014-08-01 08:00',
            '2014-08-01 09:00'
        );

        $expectedResult = [
            'message' => 'It\'s not possible to arrange meeting for anyone',
            'data' => null
        ];

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testFindAvailableTimeSlotsUTCOneAttendeeInOneDay()
    {
        $this->benchmark(true);

        $attendeeList = new AttendeeList(
            [
                new Attendee(
                    'John Doe', 'UTC', ['from' => '8:00', 'to' => '16:00'], [
                        [
                            'from' => '2014-08-01 09:00',
                            'to' => '2014-08-01 10:00'
                        ],
                        [
                            'from' => '2014-08-01 14:00',
                            'to' => '2014-08-01 15:00'
                        ]
                    ]
                )
            ]
        );

        $timezone = new DateTimeZone('UTC');
        $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
            $attendeeList,
            20,
            5,
            '2014-08-01 08:00',
            '2014-08-01 16:00'
        );

        $expectedResult = [
            'message' => null,
            'data' => [
                new DateTime('2014-08-01 08:00', $timezone),
                new DateTime('2014-08-01 08:20', $timezone),
                new DateTime('2014-08-01 08:40', $timezone),
                new DateTime('2014-08-01 10:00', $timezone),
                new DateTime('2014-08-01 10:20', $timezone)
            ]
        ];

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testFindAvailableTimeSlotsUTCManyAttendeesInOneDay()
    {
        $this->benchmark(true);

        $attendees = [
            [
                'name' => 'John Doe',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '8:00',
                    'to' => '16:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 09:00',
                        'to' => '2014-08-01 10:00'
                    ],
                    [
                        'from' => '2014-08-01 14:00',
                        'to' => '2014-08-01 15:00'
                    ]
                ]
            ],
            [
                'name' => 'Jane Doe',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '10:00',
                    'to' => '18:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 10:00',
                        'to' => '2014-08-01 12:00'
                    ]
                ]
            ],
            [
                'name' => 'John Smith',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '8:00',
                    'to' => '16:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 9:30',
                        'to' => '2014-08-01 11:30'
                    ],
                    [
                        'from' => '2014-08-01 14:15',
                        'to' => '2014-08-01 14:25'
                    ]
                ]
            ],
            [
                'name' => 'Jack Kowalsky',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '8:00',
                    'to' => '16:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 9:00',
                        'to' => '2014-08-01 10:00'
                    ],
                    [
                        'from' => '2014-08-01 11:00',
                        'to' => '2014-08-01 13:00'
                    ]
                ]
            ],
            [
                'name' => 'Sheldon Cooper',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '8:00',
                    'to' => '16:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 09:00',
                        'to' => '2014-08-01 10:00'
                    ]
                ]
            ],
            [
                'name' => 'Dean Winchester',
                'timezone' => 'UTC',
                'work' => [
                    'from' => '8:00',
                    'to' => '16:00'
                ],
                'booked' => [
                    [
                        'from' => '2014-08-01 09:00',
                        'to' => '2014-08-01 10:00'
                    ]
                ]
            ]
        ];

        $attendeeList = new AttendeeList();
        $attendeeList->setFromArray($attendees);

        $timezone = new DateTimeZone('UTC');
        $result = (new Scheduler())->setCurrentTimezone($timezone)->findAvailableTimeSlots(
            $attendeeList,
            120,
            5,
            '2014-08-01 08:00',
            '2014-08-01 16:00'
        );

        $expectedResult = [
            'message' => 'It\'s not possible to arrange meeting with everyone',
            'data' => [
                'timeSlot' => new DateTime('2014-08-01 12:00', $timezone),
                'available' => [
                    $attendeeList->offsetGet(0),
                    $attendeeList->offsetGet(1),
                    $attendeeList->offsetGet(2),
                    $attendeeList->offsetGet(4),
                    $attendeeList->offsetGet(5),
                ],
                'unavailable' => [
                    $attendeeList->offsetGet(3)
                ],
                'participants' => 5
            ]
        ];

        $this->output(__METHOD__, $this->benchmark(), $result, $expectedResult);
    }

    public function testFindAvailableTimeSlotsFromInputData()
    {
        $this->benchmark(true);

        $attendeeList = new AttendeeList();
        $attendeeList->setFromJson(file_get_contents('input_example_data.json'));

        $result = (new Scheduler())->findAvailableTimeSlots(
            $attendeeList,
            60,
            10,
            '2014-08-01 08:00',
            '2014-08-07 16:00'
        );

        $this->output(__METHOD__, $this->benchmark(), $result);
    }

    /**
     * Returns md5 of an array
     *
     * @param array $array
     * @return string
     */
    private function getArrayMd5(array $array)
    {
        array_multisort($array);
        return md5(json_encode($array));
    }

    /**
     * Count execution time
     *
     * @param bool $reset
     * @return string
     */
    private function benchmark($reset = false)
    {
        static $start = null;
        if ($reset) {
            $start = null;
        }
        if (is_null($start)) {
            $start = microtime(true);
        }
        return number_format(microtime(true) - $start, 6);
    }

    /**
     * @param string $method
     * @param string $time
     * @param mixed $result
     * @param mixed $expectedResult
     */
    private function output($method, $time, $result, $expectedResult = null)
    {
        echo $method . PHP_EOL .
            'Execution time : ' . $time . 's' . PHP_EOL;
        if (is_array($result) && is_array($expectedResult)) {
            $this->assertEqual($this->getArrayMd5($result), $this->getArrayMd5($expectedResult));
        } elseif (!is_null($expectedResult)) {
            $this->assertEqual($result, $expectedResult);
        }

        echo PHP_EOL;
    }

}
 