MeetingScheduler
================

Scheduler is a class that schedules meetings for attendees from different timezones.
In case of failure it will try to find a time-slot with maximum participants and will also return a list of available and unavailable attendees.

Whole scripts requires PHP 5.5+.

I include some tests for checking output data.
To run it call:
php test.php

To run the main script call:
php index.php

Script prints out data in JSON format.

To feed different data just modify input_example_data.json file.
To change result timezone just call setCurrentTimezone(new DateTimeZone('Time/Zone')) before starting to search.
Output data contains objects DateTime, DateTimeZone and Attendee for better further manipulations.