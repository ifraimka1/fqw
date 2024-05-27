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
 * Plugin version and other meta-data are defined here.
 *
 * @package     format_fqw
 * @copyright   2024 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $defaulttemplates;
$defaulttemplates = [

    '0' => [
        'id' => 0,
        'title' => 'По умолчанию',
        'description' => '<p dir="ltr" style="text-align: left;">Шаблон по умолчанию</p>',
        'tags' => [],
        'backupfile' => 'fqw_template.mbz',
        'preview_url' => '',
        'restrictcohort' => 0,
        'cohortids' => [],
        'restrictcategory' => 0,
        'categoryids' => [],
        'includesubcategories' => 0,
        'restrictrole' => 0,
        'roleids' => [],
        'descriptionformat' => 1,
        'format' => 'fqw',
    ],
    '1' => [
        'id' => 1,
        'title' => 'МОП ЭВМ 09.04.04',
        'description' => '<p dir="ltr" style="text-align: left;">Шаблон для МОП ЭВМ 09.04.04</p>',
        'tags' => [],
        'backupfile' => 'fqw_template.mbz',
        'preview_url' => '',
        'restrictcohort' => 0,
        'cohortids' => [],
        'restrictcategory' => 0,
        'categoryids' => [],
        'includesubcategories' => 0,
        'restrictrole' => 0,
        'roleids' => [],
        'descriptionformat' => 1,
        'format' => 'fqw',
    ],
];
