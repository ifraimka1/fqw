<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Import course mbz into existing course.
 *
 * @package    format_fqw
 * @copyright  2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_fqw;

defined('MOODLE_INTERNAL') || die();

use stdClass;

require_once($CFG->dirroot.'/course/format/kickstart/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class course_importer {

    /**
     * Import template into course.
     *
     * @param int $templateid
     * @param int $courseid
     * @throws \base_plan_exception
     * @throws \base_setting_exception
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public static function import_from_template($templateid, $courseid) {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot."/course/lib.php");
        $PAGE->set_context(\context_course::instance($courseid));
        $template = $DB->get_record('format_fqw_template', ['id' => $templateid], '*', MUST_EXIST);

        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'format_fqw', 'course_backups',
            $template->id, '', false);
        $files = array_values($files);

        if (!isset($files[0])) {
            throw new \moodle_exception('coursebackupnotset', 'format_kickstart');
        }

        $fp = get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = make_backup_temp_directory('template' . $templateid);
        $files[0]->extract_to_pathname($fp, $backuptempdir);

        self::import('template' . $templateid, $courseid);
    }

    /**
     * Import course from backup directory.
     *
     * @param int $courseid
     * @throws \base_plan_exception
     * @throws \base_setting_exception
     * @throws \dml_exception
     * @throws \restore_controller_exception
     */
    public static function import($backuptempdir, $courseid) {
        global $USER, $DB;

        $course = $DB->get_record('course', ['id' => $courseid]);
        $details = \backup_general_helper::get_backup_information($backuptempdir);
        $settings = [
            'overwrite_conf' => true,
            'course_shortname' => $course->shortname,
            'course_fullname' => $course->fullname,
            'course_startdate' => $course->startdate,
            'users' => \backup::ENROL_NEVER,
            'role_assignments' => false,
            'enrolments' => \backup::ENROL_NEVER,
            'groups' => false,
        ];

        try {
            // Now restore the course.
            $target = \backup::TARGET_EXISTING_DELETING;
            $rc = new \restore_controller($backuptempdir, $course->id, \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL, $USER->id, $target);

            foreach ($settings as $settingname => $value) {
                $setting = $rc->get_plan()->get_setting($settingname);
                if ($setting->get_status() == \base_setting::LOCKED_BY_PERMISSION) {
                    $setting->set_status(\base_setting::NOT_LOCKED);
                }
                $setting->set_value($value);
            }
            $rc->execute_precheck();
            $rc->execute_plan();
            $rc->destroy();
        } catch (\Exception $e) {
            if ($rc) {
                \core\notification::error('Restore failed with status: ' . $rc->get_status());
            }
            throw $e;
        } finally {
            // Reset some settings.
            $fullname = $course->fullname;
            $shortname = $course->shortname;
            $summary = $course->summary;
            $summaryformat = $course->summaryformat;
            $enddate = $course->enddate;
            $timecreated = $course->timecreated;
            // Reload course.
            $course = $DB->get_record('course', ['id' => $courseid]);
            $course->fullname = $fullname;
            $course->shortname = $shortname;
            $course->summary = $summary;
            $course->summaryformat = $summaryformat;
            $course->enddate = $enddate;
            $course->timecreated = $timecreated;
            $DB->update_record('course', $course);
        }
    }
}
