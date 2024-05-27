<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 *  Format base class.
 *
 * @package     format_fqw
 * @copyright   2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_fqw extends core_courseformat\base {
    
    public function uses_sections() {
        return true;
    }

    public function uses_indentation(): bool {
        return true;
    }

    public function can_delete_section($section) {
        return false;
    }
    
    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Section #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Section 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the sections course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_fqw');
        } else {
            // Use course_format::get_default_section_name implementation which
            // will display the section name in "Section n" format.
            return parent::get_default_section_name($section);
        }
    }

    // Переопределите метод, который обрабатывает редактирование секции
    public function allows_editing_section_name($section) {
        // Возвращаем false, чтобы запретить изменение имени секции
        return false;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Sections format uses the following options:
     * - coursetemplate
     * - teamslink
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        global $DB;
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseformatoptions = [
                'coursetemplate' => [
                    'default' => 1,
                    'type' => PARAM_INT,
                ],
                'opendate' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'closedate' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
            ];
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $templates = $DB->get_records_menu('format_fqw_template', null, '', 'id, title');
            $courseformatoptionsedit = [
                'coursetemplate' => [
                    'label' => get_string('coursetemplate', 'format_fqw'),
                    'element_type' => 'select',
                    'element_attributes' => array($templates),
                ],
                'opendate' => [
                    'label' => get_string('opendate', 'format_fqw'),
                    'element_type' => 'date_time_selector',
                ],
                'closedate' => [
                    'label' => get_string('closedate', 'format_fqw'),
                    'element_type' => 'date_time_selector',
                ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }
    
    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        $formatoptions = $this->get_format_options();
        $formatoptions['indentation'] = get_config('format_topics', 'indentation');
        return $formatoptions;
    }
}

/**
 * Automatically create the template.
 * @param object $template template info
 * @param int $sort sort position
 * @param object $context page context
 * @param string $component
 * @return void
 */
function format_fqw_create_template($template, $sort, $context) {

    global $DB, $CFG, $USER;
    if (!isguestuser() && isloggedin()) {
        $fs = get_file_storage();
        $draftidattach = file_get_unused_draft_itemid();
        $template->sort = $sort;
        $template->course_backup = $draftidattach;
        $template->cohortids = json_encode($template->cohortids);
        $template->categoryids = json_encode($template->categoryids);
        $template->roleids = json_encode($template->roleids);
        $template->courseformat = 0;
        $id = $DB->insert_record('format_fqw_template', $template);
        core_tag_tag::set_item_tags('format_fqw', 'format_fqw_template', $id, $context, $template->tags);
        if (isset($template->backupfile) && !empty($template->backupfile)) {
            $filerecord = new stdClass();
            $filerecord->component = 'format_fqw';
            $filerecord->contextid = $context->id;
            $filerecord->filearea = "course_backups";
            $filerecord->filepath = '/';
            $filerecord->itemid = $id;
            $filerecord->filename = $template->backupfile;
            $exist = check_record_exsist($filerecord);
            if ($exist != 1) {
                $backuppath = $CFG->dirroot . "/course/format/fqw/assets/templates/$template->backupfile";
                $fs->create_file_from_pathname($filerecord, $backuppath);
            }
        }
        return $id;
    }
}
