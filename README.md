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

#### Backup webservice

`local_ehl_course_backup` creates backup and supports parameters:

* `courseid` -  Course id
* `courseidnumber` - Course idnumber
* `courseshortname` - Course shortname
* `nousers` - Don't include user enrolment

Either of 3 course params is required to determine course that will be backed
up.

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_course_backup&courseid=25'

{
  "filesize": "36.3KB",
  "fileurl": "http://moodle.local/webservice/pluginfile.php/356/backup/course/backup-moodle2-course-25-btc_1-20210717-2305.mbz"
}
```

To retrieve the file, call `fileurl` with `token` param added:

```
$ curl "http://moodle.local/webservice/pluginfile.php/356/backup/course/backup-moodle2-course-25-btc_1-20210717-2305.mbz?token=e2add69a036c6a203ae4dc824eb89a64"
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

#### Restore webservice

`local_ehl_course_restore_backup` schedules asyncronous course backup restore and supports parameters:

* `fileitemid` - File itemid (required)
* `categoryid` - Category id to restore course into
* `courseid` -  Course id
* `courseidnumber` - Course idnumber
* `courseshortname` - Course shortname
* `callbackurl` - Callback URL used for API call on restore success

Either of 3 course params is required to determine course that will be
overwritten during restore. Specify `categoryid` if prefer restoring as new course.

`fileitemid` is `itemid` from upload file webservice response that should be used to uploading backup file, see [upload webservice documentation](https://docs.moodle.org/dev/Web_services_files_handling#File_upload) for
more details. 

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_course_restore_backup&courseid=25&fileitemid=56744917'

{
  "restoreid": "539d67f113d228ae7ce743010d189b83",
  "contextid": 811
}
```

Returned data can be optionally used to query restore status using core
webservice `core_backup_get_async_backup_progress`:

```
$ curl 'http://moodle.local/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=core_backup_get_async_backup_progress&contextid=811&backupid=539d67f113d228ae7ce743010d189b83'
```

**Note on callbackurl**

When `callbackurl` is provided, it will be used to make GET request with
configured API header and key (see plugin configuration in Site administration
-> Plugins -> Local plugins -> EHL Moodle customisation) when restore has been
completed (`course_restored` event is triggered) on the background (run on
cron using standard core functionality for asyncronous restore).  The special
Callback Logs page has been designed to make easier to identify issues with
restore or callback execution, it shows pending restores as well as failure
reason for those restores where API callback rusulted in error. Successful
restores or those made without callbackurl param are not listed in the logs.

### Quiz edit webservices

There are three webservices each desinged for dedicated section of Quiz
editing from.

Only quiz ID is required param for each webservise, others are optional. If
param is not supplied this mean it is not going to be changed. Exception is
`local_ehl_mod_quiz_update_review_setting` webservice, that due to complexity
of implementaiton requires full params matrix to be provided (any omitted
param is counted as "unticked" checkbox).

Return status `true` indicates that changes were made, `false`
mean supplied values are matching existing ones. Changes field provides more
details no actual values changed. Error is thrown if supplied params are invalid.

#### local_ehl_mod_quiz_update_timing_settings

`local_ehl_mod_quiz_update_timing_settings` updates timing settings and support
parameters:

* `quizid` - Quiz instance id (required)
* `timeopen` - Open date in Unix time stamp, setting to 0 disables
* `timeclose` - Close date in Unix time stamp, setting to 0 disables
* `timelimit` - Specify timelimit in seconds, setting to 0 disables limit
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
* `attempts` - Number of attempts allowed, 0 for unlimited
* `grademethod` - Grade method. Can be set if there is for more than 1 attempt. 1 - Highest grade, 2 - Average grade, 3 - First attempt, 4 - Last attempt

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_update_grading_settings&quizid=3&attempts=5'

{
  "status": true,
  "changes": "{\"attempts\":\" => 5\"}"
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

### Quiz group overrides

These webservices are desinged for managing group overrides. To retrieve
existing group instances in the course, use `core_group_get_course_groups`.

#### local_ehl_mod_quiz_create_group_override

`local_ehl_mod_quiz_create_group_override` creates group override and supports
parameters:

* `quizid` - Quiz instance id (required)
* `groupid` - Group instance id (required),
* `timeopen` - Open date in Unix time stamp, setting to 0 disables
* `timeclose` - Close date in Unix time stamp, setting to 0 disables
* `timelimit` - Specify timelimit in seconds, setting to 0 disables limit
* `attempts` - Number of attempts allowed, 0 for unlimited

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_create_group_override&quizid=3&groupid=28&timeopen=1625611102'

{
  "status": true,
  "overrideid": 10
}
```

#### local_ehl_mod_quiz_update_group_override

`local_ehl_mod_quiz_update_group_override` updates existing group override and
supports parameters:

* `overrideid` - Quiz group override id (required)
* `timeopen` - Open date in Unix time stamp, setting to 0 disables
* `timeclose` - Close date in Unix time stamp, setting to 0 disables
* `timelimit` - Specify timelimit in seconds, setting to 0 disables limit
* `attempts` - Number of attempts allowed, 0 for unlimited

Not specified parameters are treated as "unchanged" (remain the same). Return
status false if supplied settings are matching existing, so no update is made.

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_update_group_override&overrideid=10&attempts=5'

{
  "status": true,
  "changes": "{\"attempts\":\"0 => 5\"}"
}
```

#### local_ehl_mod_quiz_list_group_overrides

`local_ehl_mod_quiz_list_group_overrides` lists existing group overrides and
supports parameters:

* `quizid` - Quiz instance id (required)

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_list_group_overrides&quizid=3'

{
  "overrides": [
    {
      "id": 10,
      "quiz": 3,
      "groupid": 29,
      "timeopen": 0,
      "timeclose": 0,
      "timelimit": 0,
      "attempts": 5
    }
  ]
}
```


#### local_ehl_mod_quiz_delete_group_override

`local_ehl_mod_quiz_delete_group_override` deletes group override and supports
parameters:

* `overrideid` - Quiz group override id (required)

CLI query example:
```
$ curl 'https://SITENAME/webservice/rest/server.php?moodlewsrestformat=json' \
--data 'wstoken=e2add69a036c6a203ae4dc824eb89a64&wsfunction=local_ehl_mod_quiz_delete_group_override&overrideid=10'

{
  "status": true
}
```

