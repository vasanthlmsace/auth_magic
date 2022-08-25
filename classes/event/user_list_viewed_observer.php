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
class user_list_viewed_observer {

    /**
     * Create user list viewed request when the user is deleted.
     *
     * @param \core\event\user_list_viewed $event
     */
    public static function create_user_list_viewed_request(\core\event\user_list_viewed $event) {
        global $PAGE, $CFG;
        require_once($CFG->dirroot. "/auth/magic/lib.php");
        $data = $event->get_data();
        if (isset($data['objecttable']) && isset($data['courseid']) && $data['contextlevel'] == CONTEXT_COURSE) {
            $courseid = $data['courseid'];
            $coursecontext = \context_course::instance($courseid);
            $params['hascourseregister'] = has_capability("auth/magic:cancoursequickregistration", $coursecontext)
                && auth_magic_is_course_manual_enrollment($courseid);
            $url = new \moodle_url('/auth/magic/registration.php');
            $params['url'] = $url->out(false);
            $params['courseid'] = $courseid;
            $params['strquickregister'] = get_string('quickregistration', 'auth_magic');
            $PAGE->requires->js_call_amd('auth_magic/authmagic', 'init', array($params));
        }
    }
}
