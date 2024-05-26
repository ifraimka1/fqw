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
 * Plugin upgrade steps are defined here.
 *
 * @package     format_fqw
 * @category    upgrade
 * @copyright   2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute format_fqw upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_format_fqw_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024052600) {

        // Define table format_fqw_template to be created.
        $table = new xmldb_table('format_fqw_template');

        // Adding fields to table format_fqw_template.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('preview_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('restrictcohort', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('restrictcategory', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('restrictrole', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('cohortids', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('categoryids', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('includesubcategories', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('roleids', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('courseformat', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '1');
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, null, null, '1');
        $table->add_field('format', XMLDB_TYPE_CHAR, '40', null, null, null, null);

        // Adding keys to table format_fqw_template.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for format_fqw_template.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table format_fqw_gek_assignment to be created.
        $table = new xmldb_table('format_fqw_gek_assignment');

        // Adding fields to table format_fqw_gek_assignment.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_fqw_gek_assignment.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
 
        // Conditionally launch create table for format_fqw_gek_assignment.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        } 

        // Fqw savepoint reached.
        upgrade_plugin_savepoint(true, 2024052600, 'format', 'fqw');
    }

    return true;
}
