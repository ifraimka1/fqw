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
 * Event observer function definition and returns.
 *
 * @package     format_fqw
 * @copyright   2023 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_fqw;
use stdClass;

/**
 * Event observer class define.
 */
class observer {

    /**
     * Callback function will import template to the course.
     * @param object $event event data
     * @return void
     */
    public static function format_fqw_restore_template(\core\event\course_created $event) {
        global $DB;

        $createdcourse = $DB->get_record('course', ['id' => $event->courseid], 'format', MUST_EXIST);

        // Проверяем, что формат курса 'fqw'
        if ($createdcourse->format == 'fqw') {
            // Получаем значение параметра шаблона курса из таблицы course_format_options
            $template = $DB->get_record('course_format_options', [
                'courseid' => $event->courseid,
                'format' => 'fqw',
                'name' => 'coursetemplate'
            ], 'value', MUST_EXIST);

            $templateid = $template->value;
            course_importer::import_from_template($templateid, $event->courseid);
            self::format_fqw_update_assignment_dates($event->courseid);
        }
    }

    public static function format_fqw_update_course(\core\event\course_updated $event) {
        global $DB;

        $updatedcourse = $DB->get_record('course', ['id' => $event->courseid], 'format', MUST_EXIST);

        if ($updatedcourse->format == 'fqw') {

            $data = $event->other["updatedfields"];

            $opendate = isset($data['opendate']) ? $data['opendate'] : null;
            $closedate = isset($data['closedate']) ? $data['closedate'] : null;

            if ($opendate !== null || $closedate !== null) {
                self::format_fqw_update_assignment_dates($event->courseid);
            }
        }
    }

    private static function format_fqw_update_assignment_dates($courseid) {
        global $DB;

        $opendate = $DB->get_field('course_format_options', 'value', ['courseid' => $courseid, 'format' => 'fqw', 'name' => 'opendate']);
        $closedate = $DB->get_field('course_format_options', 'value', ['courseid' => $courseid, 'format' => 'fqw', 'name' => 'closedate']);

        if ($opendate || $closedate) {
            $assignments = $DB->get_records('assign', ['course' => $courseid]);

            foreach ($assignments as $assignment) {
                $update = false;
                $assignmentdata = new stdClass();
                $assignmentdata->id = $assignment->id;

                if ($opendate) {
                    $assignmentdata->allowsubmissionsfromdate = $opendate;
                    $update = true;
                }
                if ($closedate) {
                    $assignmentdata->duedate = $closedate;
                    $update = true;
                }

                if ($update) {
                    $DB->update_record('assign', $assignmentdata);
                }
            }
        }
    }

    /**
     * Will create assignment for gekmember.
     * @param object $event event data
     * @return void
     */
    public static function format_fqw_gekmember_assigned_handler(\core\event\role_assigned $event) {
        global $DB, $CFG;

        // Извлекаем данные события
        $context = $event->get_context();
        $objectid = $event->objectid;
        $userid = $event->relateduserid;

        // Проверяем, что контекст является контекстом курса
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $courseid = $context->instanceid;

        $role = $DB->get_record('role', array('id' => $objectid), '*', MUST_EXIST);

        if ($role->shortname != 'gekmember' && $role->shortname != 'predsedatel') {
            return;
        }

        // Получаем информацию о пользователе
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        $module = $DB->get_record('modules', array('name' => 'assign'), '*', MUST_EXIST);

        $section = 2; // Добавить в верхний раздел курса

        $firstsecondnames = explode(" ", $user->firstname);
        $userinitials = '';
        foreach ($firstsecondnames as $name) {
            $userinitials .= mb_substr($name, 0, 1).".";
        }
        $modulename = $role->name.' - '.$user->lastname." ".$userinitials;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        // Создаем задание с фамилией пользователя в названии
        self::format_fqw_create_gekmebmer_assignment($courseid, $module->id, $section, $modulename, $userid);
    }

    private static function format_fqw_create_gekmebmer_assignment($courseid, $moduleid, $section, $name, $userid) {
        global $DB;

        $assignment = new stdClass();
        $assignment->course = $courseid;
        $assignment->name = $name;
        $assignment->intro = '';
        $assignment->introformat = FORMAT_HTML;
        $assignment->duedate = 0;
        $assignment->allowsubmissionsfromdate = 0;
        $assignment->grade = 100;
        $assignment->timemodified = time();
        $assignment->requiresubmissionstatement = 0;

        $assignment->id = $DB->insert_record('assign', $assignment);

        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $moduleid;
        $cm->instance = $assignment->id;
        $cm->section = $section;
        $cm->visible = 1;
        $cm->visibleold = 1;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->groupmembersonly = 0;
        $cm->completion = 0;
        $cm->completiongradeitemnumber = null;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->availablefrom = 0;
        $cm->availableuntil = 0;
        $cm->showavailability = 0;
        $cm->showdescription = 1;
        $cm->added = time();

        $cmid = add_course_module($cm);

        course_add_cm_to_section($courseid, $cmid, $section);

        rebuild_course_cache($courseid, true);

        // Добавляем условие доступности
        self::add_availability_condition($cmid, $courseid, 'Допуск к защите');

        // Сохраняем ID созданного задания для последующего удаления
        $assignment->cmid = $cmid;
        $assignment->courseid = $courseid;
        $assignment->userid = $userid;
        $DB->insert_record('format_fqw_gek_assignment', $assignment);
    }

    private static function add_availability_condition($cmid, $courseid, $completion_itemname) {
        global $DB;
    
        // Получаем ID задания "Допуск к защите"
        $completion_module = $DB->get_record_sql(
            "SELECT cm.id
             FROM {course_modules} cm
             JOIN {modules} m ON cm.module = m.id
             JOIN {assign} a ON cm.instance = a.id
             WHERE cm.course = ? AND a.name = ?",
            array($courseid, $completion_itemname)
        );
    
        if ($completion_module) {
            // Формируем условие доступности
            $condition = array(
                'type' => 'completion',
                'cm' => $completion_module->id,
                'e' => 1 // Условие: должно быть выполнено
            );
    
            $availability = array(
                'op' => '|',
                'c' => array($condition),
                'show' => true
            );
    
            // Обновляем условие доступности
            $DB->set_field('course_modules', 'availability', json_encode($availability), array('id' => $cmid));
        }
    }
    

    public static function format_fqw_gekmember_unassigned_handler(\core\event\role_unassigned $event) {
        global $DB;

        // Извлекаем данные события
        $context = $event->get_context();
        $roleid = $event->objectid;
        $userid = $event->relateduserid;

        // Проверяем, что контекст является контекстом курса
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $courseid = $context->instanceid;

        // Получаем информацию о роли
        $role = $DB->get_record('role', array('id' => $roleid), '*', MUST_EXIST);

        // Проверяем, что роль соответствует "Член ГЭК"
        if ($role->shortname != 'gekmember' && $role->shortname != 'predsedatel') {
            return;
        }

        // Удаляем задание
        self::format_fqw_delete_gekmebmer_assignment($courseid, $userid);
    }

    public static function format_fqw_gekmember_unenroled_handler(\core\event\user_enrolment_deleted $event) {
        global $DB;

        // Извлекаем данные события
        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // Удаляем задание
        self::format_fqw_delete_gekmebmer_assignment($courseid, $userid);
    }

    private static function format_fqw_delete_gekmebmer_assignment($courseid, $userid) {
        global $DB;

        // Ищем задание, созданное для данного пользователя в данном курсе
        $assignment = $DB->get_record('format_fqw_gek_assignment', array('courseid' => $courseid, 'userid' => $userid), '*', IGNORE_MISSING);

        if ($assignment) {
            // Удаляем задание
            course_delete_module($assignment->cmid);

            // Удаляем запись из таблицы 'format_fqw_gek_assignment'
            $DB->delete_records('format_fqw_gek_assignment', array('id' => $assignment->id));
        }
    }
}
