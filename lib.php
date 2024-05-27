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

/**
 *  Format base class.
 *
 * @package     format_fqw
 * @copyright   2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

class format_fqw extends core_courseformat\base {

    public function uses_indentation(): bool {
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
     * Topics format uses the following options:
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
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
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
