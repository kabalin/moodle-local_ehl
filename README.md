## Moodle customisations

### Backup

This is a copy of backup script from `admin/cli/backup.php` with following
changes:

* Add param `--nousers` (alias `-nu`) to exclude user enrolments.
* Add param `--courseidnumber` to retrieve course for backup by its `idnumber`
* Use `cli_*` methods for output and other general code improvements.

Usage:
```
sudo -u www-data /usr/bin/php local/ehl/cli/backup.php --courseid=2 -nu --destination=/tmp
```

### Restore

This is a copy of restore script from `admin/cli/restore-backup.php` with following
changes:

* Add params `--courseid` (`--courseshortname`, `--courseidnumber`) to restore into
  existing course with *overwriting* course content. This step promts to
  confirmation by defalt.
* Add param `--force` to supress overwriting confirmation.
* Use `cli_*` methods for output and other general code improvements.

Usage:
```
sudo -u www-data /usr/bin/php local/ehl/cli/restore_backup.php --file=/tmp/file.mbz --courseid=2
```

### Quiz edit webservices

There are three webservices each desinged for dedicated section of Quiz
editing from.

Only quiz ID is required param for each webservise, others are optional. If
param is not supplied this mean it is not going to be changed. Exception is
`local_ehl_mod_quiz_update_review_setting` webservice, that due to complexity
requires full params matrix to be provided (any omitted param is counted as
"unticked" checkbox).

Return status `true` indicates that changes were made, `false`
mean supplied values are matching existing ones. Changes field provides more
details no actual values changed. Error is thrown if supplied params are invalid.

#### local_ehl_mod_quiz_update_timing_settings

`local_ehl_mod_quiz_update_timing_settings` updates timing settings and support
parameters:

* `quizid` - Quiz instance id (required)
* `timeopen` - Open date in Unix time stamp, setting to 0 disables
* `timeclose` - Close date in Unix time stamp, setting to 0 disables
* `timelimit` - Specify timelimit, setting to 0 disables limit
* `overduehandling` - Overdue handling setting: autosubmit, graceperiod, autoabandon
* `graceperiod` - Grace period in seconds. Can only be set when Overdue handling is set to graceperiod

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_update_timing_settings&quizid=3&timeopen=1625611102'

{
  "status": true,
  "changes": "{\"timeopen\":\"1625611080 => 1625611102\"}"
}
```

#### local_ehl_mod_quiz_update_grading_settings

`local_ehl_mod_quiz_update_grading_settings` updates grading settings and support
parameters:

* `quizid` - Quiz instance id (required)
* `attemptsallowed` - Number of attempts allowed, 0 for unlimited
* `grademethod` - Grade method. Can be set if there is for more than 1 attempt. 1 - Highest grade, 2 - Average grade, 3 - First attempt, 4 - Last attempt

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_update_grading_settings&quizid=3&attemptsallowed=5'

{
  "status": true,
  "changes": "{\"attemptsallowed\":\" => 5\"}"
}
```

#### local_ehl_mod_quiz_update_review_settings

`local_ehl_mod_quiz_update_review_settings` updates grading settings and support
parameters:

* `quizid` - quiz instance id (required)
* `attempt`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `correctness`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `marks`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `specificfeedback`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `generalfeedback`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `rightanswer`
  * `during`
  * `immediately`
  * `open`
  * `closed`
* `overallfeedback`
  * `during`
  * `immediately`
  * `open`
  * `closed`

Full settings matrix needs to be supplied. Any omitted param is regarded as
`false` (or "unticked" in the form).

CLI query example (ticks off "Attempt" at "During the attempt", "Attempt" at
"Immediately after the attempt", "Marks" at "Immediately after the attempt"
and "Marks" at "Later, while the quiz is still open", all other checkboxes are
unticked):
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_update_review_settings&quizid=3&attempt[during]=1&attempt[immediately]=1&marks[immediately]=1&marks[open]=1'

{
  "status": true,
  "changes": "{\"attempt\":\"65536 => 69632\",\"marks\":\"0 => 4352\"}"
}
```

