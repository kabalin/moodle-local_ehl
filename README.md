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

