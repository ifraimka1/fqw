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
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_created',
        'callback'  => '\format_fqw\observer::format_fqw_restore_template',
    ],
    [
        'eventname' => '\core\event\course_updated',
        'callback'  => '\format_fqw\observer::format_fqw_update_course',
    ],
    [
        'eventname' => '\core\event\role_assigned',
        'callback'  => '\format_fqw\observer::format_fqw_gekmember_assigned_handler',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback'  => '\format_fqw\observer::format_fqw_gekmember_unassigned_handler',
    ],
    [
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback'  => '\format_fqw\observer::format_fqw_gekmember_unenroled_handler',
    ],
];
