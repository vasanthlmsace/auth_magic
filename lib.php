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
 * Authentication Plugin: Magic Authentication lib functions.
 *
 *
 * @package     auth_magic
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\output\myprofile\tree;

/**
 * Get available manual enrolment courses.
 * @return array courses.
 */
function auth_magic_get_courses_for_registration() {
    global $PAGE, $DB;
    $courses = [];
    $coursesinfo = $DB->get_records('course', null, 'id DESC');
    if (!empty($coursesinfo)) {
        foreach ($coursesinfo as $info) {
            $instances = enrol_get_instances($info->id, true);
            // Make sure manual enrolments instance exists.
            foreach ($instances as $instance) {
                if ($instance->enrol == 'manual') {
                    $courselist = new core_course_list_element($info);
                    $courses[$info->id] = $courselist->get_formatted_fullname();
                }
            }
        }
    }
    return $courses;
}

/**
 * Get available manual enrolment given course.
 * @param int $courseid
 * @return bool status.
 */
function auth_magic_is_course_manual_enrollment($courseid) {
    $instances = enrol_get_instances($courseid, true);
    // Make sure manual enrolments instance exists.
    foreach ($instances as $instance) {
        if ($instance->enrol == 'manual') {
            return true;
        }
    }
    return false;
}

/**
 * Display user courses.
 * @param int $userid
 * @return string
 */
function auth_magic_user_courses($userid) {
    $courses = enrol_get_all_users_courses($userid, true, array('fullname'), 'fullname');
    $fullnames = array_column($courses, 'fullname');
    $content = '';
    if (count($fullnames) > 3) {
        $show = array_slice($fullnames, 0, 3);
        $more = array_slice($fullnames, 3);
        $content .= implode(',', $show);
        $content .= "<details><summary>" . get_string('more', 'auth_magic', count($more)) . "</summary>";
        $content .= implode(',', $more). "</details>";
    } else {
        $content .= implode(',', $fullnames);
    }
    return $content;

}

/**
 * Enroll user into the course.
 * @param int $courseid courseid
 * @param object $user user
 * @param int $enrolmentduration duration
 * @return bool enrol or not
 */
function auth_magic_enroll_course_user($courseid, $user, $enrolmentduration = 0) {
    global $DB;
    $context = context_course::instance($courseid);
    $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
    $enrolmanual = enrol_get_plugin('manual');
    if ($enrolmanual &&  !empty($instance)) {
        if ($enrolmanual->allow_enrol($instance)) {
            $timeend = 0;
            if ($enrolmentduration) {
                $timeend = time() + $enrolmentduration;
            }
            $roleid = get_config('auth_magic', 'enrolmentrole');
            $enrolmanual->enrol_user($instance, $user->id, $roleid, time(), $timeend);
            return true;
        }
    }
    return false;
}

/**
 * Display user created confim box
 * @param array $args
 * @return string
 */
function auth_magic_output_fragment_display_box_content($args) {
    global $DB, $OUTPUT;
    $templatecontext = [];
    $user = $DB->get_record('user', array('id' => $args['user']));
    $profileurl = new moodle_url('/user/profile.php', array('id' => $user->id));
    if ($args['course']) {
        $course = get_course($args['course']);
        $courseelement = new core_course_list_element($course);
        $templatecontext['coursename'] = $courseelement->get_formatted_fullname();
        $profileurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
    }
    $userkeyinfo = auth_magic_get_user_userkeyinfo($user->id);
    $templatecontext['username'] = fullname($user);
    $templatecontext['magicinvitation'] = $userkeyinfo->magicinvitation;
    $templatecontext['profileurl'] = $profileurl->out(false);
    return $OUTPUT->render_from_template('auth_magic/modalbox', $templatecontext);
}

/**
 * Get the logininfo for given user.
 * @param int $userid
 * @return object
 */
function auth_magic_get_user_userkeyinfo($userid) {
    global $DB;
    return $DB->get_record('auth_magic_loginlinks', array('userid' => $userid));
}

/**
 * Get user login link
 * @param int $userid
 * @return string url.
 */
function auth_magic_get_user_login_link($userid) {
    global $DB;
    return $DB->get_field('auth_magic_loginlinks', 'magiclogin', array('userid' => $userid), 'loginurl');
}


/**
 * Get user invitation link
 * @param int $userid
 * @return string url.
 */
function auth_magic_get_user_invitation_link($userid) {
    global $DB;
    return $DB->get_field('auth_magic_loginlinks', 'magicinvitation', array('userid' => $userid), 'loginurl');
}


/**
 * Defines learningtools nodes for my profile navigation tree.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser is the user viewing profile, current user ?
 * @param stdClass $course course object
 *
 * @return bool
 */
function auth_magic_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course) {
    global $USER;
    if (!is_enabled_auth('magic')) {
        return;
    }
    // Get the learningtools category.
    if (!array_key_exists('magicauth', $tree->__get('categories'))) {
        // Create the category.
        $categoryname = get_string('configtitle', 'auth_magic');
        $category = new core_user\output\myprofile\category('magicauth', $categoryname, 'privacyandpolicies');
        $tree->add_category($category);
    } else {
        // Get the existing category.
        $category = $tree->__get('categories')['magicauth'];
    }
    $systemcontext = context_system::instance();
    if ($iscurrentuser) {
        // Quick registration.
        if (!empty($course)) {
            $coursecontext = context_course::instance($course->id);
            if (has_capability('auth/magic:cancoursequickregistration', $coursecontext) &&
                auth_magic_is_course_manual_enrollment($course->id)) {
                $registrationurl = new moodle_url('/auth/magic/registration.php', array('courseid' => $course->id));
                $registersnode = new core_user\output\myprofile\node('magicauth', 'quickregistration',
                    get_string('quickregistration', 'auth_magic'), null, $registrationurl);
                $tree->add_node($registersnode);
            }
        } else {
            $usercontext = context_user::instance($USER->id);
            if (has_capability('auth/magic:viewloginlinks', $systemcontext)) {
                $magickeysurl = new moodle_url('/auth/magic/listusers.php');
                $magickeysnode = new core_user\output\myprofile\node('magicauth', 'magickeys',
                    get_string('userkeyslist', 'auth_magic'), null, $magickeysurl);
                $tree->add_node($magickeysnode);
            } else if (auth_magic_is_parent_see_child_magiclinks()) {
                $magickeysurl = new moodle_url('/auth/magic/listusers.php', array('userid' => $USER->id));
                $magickeysnode = new core_user\output\myprofile\node('magicauth', 'magickeys',
                    get_string('userkeyslist', 'auth_magic'), null, $magickeysurl);
                $tree->add_node($magickeysnode);
            }

            if (has_capability('auth/magic:cansitequickregistration', $systemcontext)) {
                $registrationurl = new moodle_url('/auth/magic/registration.php');
                $registersnode = new core_user\output\myprofile\node('magicauth', 'quickregistration',
                    get_string('quickregistration', 'auth_magic'), null, $registrationurl);
                $tree->add_node($registersnode);
            }
        }
    }
}

/**
 * Get the child users for the parent.
 * @param int $parentid
 * @param bool $checkparent
 * @return array child users.
 */
function auth_magic_get_parent_child_users($parentid, $checkparent = false) {
    global $DB;
    $isparent = false;
    $users = [];
    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = ".CONTEXT_USER, array($parentid))) {
        $users = array_keys($usercontexts);
        $isparent = true;
    }
    if ($checkparent) {
        return $isparent;
    }
    return $users;
}

/**
 * Parent can see the child user keys
 */
function auth_magic_is_parent_see_child_magiclinks() {
    global $USER;
    $status = false;
    $users = auth_magic_get_parent_child_users($USER->id);
    if (!empty($users)) {
        $user = current($users);
        $usercontext = context_user::instance($user);
        if (has_capability('auth/magic:viewchildloginlinks', $usercontext)) {
            $status = true;
        }
    }
    return $status;
}

/**
 * Send message to user using message api.
 *
 * @param  mixed $userto
 * @param  mixed $subject
 * @param  mixed $messageplain
 * @param  mixed $messagehtml
 * @param  mixed $courseid
 * @return bool message status
 */
function auth_magic_messagetouser($userto, $subject, $messageplain, $messagehtml, $courseid = null) {
    $eventdata = new \core\message\message();
    $eventdata->name = 'auth_magic';
    $eventdata->component = 'auth_magic';
    $eventdata->modulename = 'moodle';
    $eventdata->courseid = empty($courseid) ? SITEID : $courseid;
    $eventdata->userfrom = core_user::get_support_user();
    $eventdata->userto = $userto;
    $eventdata->subject = $subject;
    $eventdata->fullmessage = $messageplain;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = $messagehtml;
    $eventdata->smallmessage = $subject;

    if (message_send($eventdata)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Sent the invitation link to the user.
 * @param int $userid
 * @return bool message status
 */
function auth_magic_sent_invitation_user($userid) {
    $site = get_site();
    $user = \core_user::get_user($userid);
    $invitationlink = auth_magic_get_user_invitation_link($userid);
    $subject = get_string('loginsubject', 'auth_magic', format_string($site->fullname));
    // Lang data.
    $data = new stdClass();
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();
    $data->fullname = fullname($user);
    $data->link = $invitationlink;
    $messageplain = get_string('invitationmessage', 'auth_magic', $data); // Plain text.
    $messagehtml = text_to_html($messageplain, false, false, true);
    $user->mailformat = 1;  // Always send HTML version as well.
    return auth_magic_messagetouser($user, $subject, $messageplain, $messagehtml);
}

/**
 * Sent the login link to the user.
 * @param int $userid
 * @param bool $otherauth
 * @return bool message status
 */
function auth_magic_sent_loginlink_touser($userid, $otherauth = false) {
    $site = get_site();
    $user = \core_user::get_user($userid);
    if ($otherauth) {
        $auth = get_auth_plugin('magic');
        $auth->create_magic_instance($user, false);
    }
    $loginlink = auth_magic_get_user_login_link($userid);
    $subject = get_string('loginsubject', 'auth_magic', format_string($site->fullname));
    $data = new stdClass();
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();
    $data->fullname = fullname($user);
    $data->link = $loginlink;
    $messageplain = get_string('loginlinknmessage', 'auth_magic', $data);
    $messagehtml = text_to_html($messageplain, false, false, true);
    $user->mailformat = 1;  // Always send HTML version as well.
    return auth_magic_messagetouser($user, $subject, $messageplain, $messagehtml);

}

/**
 * Sent the information for non magic auth users.
 * @param int $userid
 * @return void
 */
function auth_magic_requiredmail_magic_authentication($userid) {
    $site = get_site();
    $user = \core_user::get_user($userid);
    $forgothtml = html_writer::link(new moodle_url('/login/forgot_password.php'), get_string('forgotten'));
    $subject = get_string('loginsubject', 'auth_magic', format_string($site->fullname));
    $data = new stdClass();
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();
    $data->fullname = fullname($user);
    $data->forgothtml = $forgothtml;
    $messageplain = get_string('preventmagicauthmessage', 'auth_magic', $data);
    $messagehtml = text_to_html($messageplain, false, false, true);
    $user->mailformat = 1;
    return auth_magic_messagetouser($user, $subject, $messageplain, $messagehtml);
}
