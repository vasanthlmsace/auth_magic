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
 * Admin settings and defaults
 *
 * @package auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Enrolment duration.
    $name = "auth_magic/enrolmentduration";
    $title = get_string("defaultenrolmentduration", "auth_magic");
    $desc = "";
    $setting = new admin_setting_configduration($name, $title, $desc, 0);
    $settings->add($setting);

    // Enrollment role.
    $options = get_default_enrol_roles(context_system::instance());
    $student = get_archetype_roles('student');
    $student = reset($student);
    $name = "auth_magic/enrolmentrole";
    $title = get_string("defaultenrolmentrole", "auth_magic");
    $desc = "";
    $setting = new admin_setting_configselect($name, $title, $desc, $student->id ?? null, $options);
    $settings->add($setting);

     // Magic login link expiry.
     $name = "auth_magic/invitationexpiry";
     $title = get_string("invitationexpiry", "auth_magic");
     $desc = "";
     $setting = new admin_setting_configduration($name, $title, $desc, 1 * WEEKSECS);
     $settings->add($setting);

    // Magic login link expiry.
    $name = "auth_magic/loginexpiry";
    $title = get_string("loginexpiry", "auth_magic");
    $desc = "";
    $setting = new admin_setting_configduration($name, $title, $desc, 4 * HOURSECS);
    $settings->add($setting);

    // Supported authentication method.
    $options = [
        0 => get_string('magiconly', 'auth_magic'),
        1 => get_string('anymethod', 'auth_magic'),
    ];
    $name = "auth_magic/authmethod";
    $title = get_string("strsupportauth", "auth_magic");
    $desc = "";
    $setting = new admin_setting_configselect($name, $title, $desc, 0, $options);
    $settings->add($setting);



    // Owener account role.
    $options = [];
    $options[0] = get_string('none');
    $usercontextroles = get_roles_for_contextlevels(CONTEXT_USER);
    if ($usercontextroles) {
        list($rolesql, $roleparams) = $DB->get_in_or_equal($usercontextroles);
        $sql = "SELECT id, name FROM {role} WHERE id $rolesql";
        $roles = $DB->get_records_sql($sql, $roleparams);
        $options += array_column($roles, 'name', 'id');
    }
    $name = "auth_magic/owneraccountrole";
    $title = get_string("strowneraccountrole", "auth_magic");
    $desc = "";
    $setting = new admin_setting_configselect($name, $title, $desc, 0, $options);
    $settings->add($setting);

}

if (is_enabled_auth('magic')) {
    $ADMIN->add('accounts', new admin_externalpage('auth_magic_quickregistration',
        get_string('quickregistration', 'auth_magic'),
        new moodle_url('/auth/magic/registration.php'), ['auth/magic:cansitequickregistration']));

    $ADMIN->add('accounts', new admin_externalpage('auth_magic_loginlinks',
        get_string('listofmagiclink', 'auth_magic'),
        new moodle_url('/auth/magic/listusers.php')));
}
