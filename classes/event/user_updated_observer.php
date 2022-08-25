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
 * Event observers supported by this plugin.
 *
 * @package    auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_magic\event;

/**
 * Event observers supported by this plugin.
 *
 * @package    auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_updated_observer {

    /**
     * Create user data update request when the user is updated.
     *
     * @param \core\event\user_updated $event
     */
    public static function create_update_data_request(\core\event\user_updated $event) {
        global $DB, $USER;
        $userid = $event->objectid;
        $usercontext = \context_user::instance($userid);
        $user = \core_user::get_user($userid);
        if ($user->auth == 'magic') {
            // If check the parent role assign or not.
            if ($roleid = get_config('auth_magic', 'owneraccountrole')) {
                role_assign($roleid, $USER->id, $usercontext->id);
            }
            $auth = get_auth_plugin('magic');
            // Request login url.
            $auth->create_magic_instance($user);
        }
        return true;
    }
}
