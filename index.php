<?php
if (version_compare(phpversion(), '5.5', '<')) {
    die('Required PHP 5.5+');
}

require('autoload.php');

$attendeeList = new AttendeeList();
$attendeeList->setFromJson(file_get_contents('input_example_data.json'));
$result = (new Scheduler())->findAvailableTimeSlots($attendeeList, 60, 20, '2014-08-01 08:00', '2014-08-07 16:00');

echo json_encode($result);
