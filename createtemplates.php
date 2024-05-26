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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     format_fqw
 * @copyright   2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(__DIR__."/defaulttemplates.php");

/**
 * Install templates
 */
function install_templates() {
    global $defaulttemplates, $DB, $CFG;
    $context = context_system::instance();
    $cnt = $DB->count_records('format_fqw_template');
    if (!empty($defaulttemplates)) {
        $templates = isset($CFG->fqw_templates) ? explode(",", $CFG->fqw_templates) : [];
        foreach ($defaulttemplates as $template) {
            $template = (object) $template;
            // Create template.
            $cnt++;
            $templateid = format_fqw_create_template($template, $cnt, $context);
            if (!array_search($templateid, $templates)) {
                array_push($templates, $templateid);
            }
        }
        set_config('fqw_templates', implode(',', $templates));
    }
}