<?php
if (version_compare(phpversion(), '5.5', '<')) {
    die('Required PHP 5.5+');
}

require('autoload.php');

$calendarTest = new SchedulerTest();
$calendarTest->testWrongTimeSlotNumber();
$calendarTest->testWrongMeetingLength();
$calendarTest->testWrongTimeInterval();
$calendarTest->testFindAvailableTimeSlotsMinimal();
$calendarTest->testFindAvailableTimeSlotsUTCOneAttendeeInOneDay();
$calendarTest->testFindAvailableTimeSlotsUTCManyAttendeesInOneDay();
$calendarTest->testFindAvailableTimeSlotsFromInputData();
