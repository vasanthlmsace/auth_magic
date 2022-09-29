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
 * Create a simple course with the Kickstart format as default.
 *
 * @package    auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot. "/auth/magic/classes/form/quickregistration_form.php");
require_once("$CFG->libdir/adminlib.php");

if (!is_enabled_auth('magic')) {
    throw new moodle_exception(get_string('pluginisdisabled', 'auth_magic'));
}

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$return = optional_param('return', 0, PARAM_INT);
$coursebase = optional_param('coursebase', 0, PARAM_INT);
$userexist = optional_param('userexist', false, PARAM_BOOL);
require_login();
$url = new moodle_url('/auth/magic/registration.php', array('courseid' => $courseid));
$PAGE->set_url($url);
$strquickregistration = get_string('quickregistration', 'auth_magic');
if (!$courseid) {
    $context = context_system::instance();
    $PAGE->set_context($context);
    require_capability("auth/magic:cansitequickregistration", $context);
    if (is_siteadmin()) {
        $PAGE->set_pagelayout('admin');
        admin_externalpage_setup('auth_magic_quickregistration');
    } else {
        if ($profilenode = $PAGE->settingsnav->find('myprofile', null)) {
            $profilenode->make_active();
        }
        $PAGE->navbar->add($strquickregistration, $url);
        $PAGE->set_title("$SITE->fullname: ". $strquickregistration);
        $PAGE->set_heading($SITE->fullname);
    }
} else {
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
    require_capability("auth/magic:cancoursequickregistration", $context);
    $course = get_course($courseid);
    require_login($course);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_course($course);
    $PAGE->set_title("$course->shortname: ". $strquickregistration);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strquickregistration, $url);
}
$PAGE->add_body_class("auth-magic");

// Creating new user.
$user = new stdClass();
$user->id = -1;
$user->auth = 'manual';
$user->confirmed = 1;
$user->deleted = 0;
$user->timezone = '99';

if ($return) {
    $params = array();
    $params['enrolstatus'] = true;
    $params['strconfirm'] = get_string('strconfirm', 'auth_magic');
    $params['contextid'] = $context->id;
    $params['course'] = $coursebase;
    $params['user'] = $userid;
    $params['returnurl'] = $PAGE->url->out();
    $params['userexist'] = $userexist;
    $PAGE->requires->js_call_amd('auth_magic/authmagic', 'init', array($params));
}
$form = new quickregistration_form($PAGE->url->out(), ['courseid' => $courseid]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/auth/magic/registration.php', array('courseid' => $courseid)));
} else if ($usernew = $form->get_data()) {
    $coursebase = isset($usernew->course) ? $usernew->course : false;
    $enrolmentduration = $usernew->enrolmentduration;
    $usercreated = false;
    $existuser = $DB->get_record('user', array('email' => $usernew->email));
    if (!$existuser) {
        $usernew->timemodified = time();
        if ($usernew->id == -1) {
            $usernew->username = $usernew->email;
            $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
            $usernew->confirmed  = 1;
            $usernew->timecreated = time();
            $usernew->id = user_create_user($usernew, false, false);
            $usercreated = true;
        }
        $usercontext = context_user::instance($usernew->id);
        // Update mail bounces.
        useredit_update_bounces($user, $usernew);

        // Update forum track preference.
        useredit_update_trackforums($user, $usernew);

        // Reload from db.
        $usernew = $DB->get_record('user', array('id' => $usernew->id));

        // Trigger update/create event, after all fields are stored.
        if ($usercreated) {
            \core\event\user_created::create_from_userid($usernew->id)->trigger();
        } else {
            \core\event\user_updated::create_from_userid($usernew->id)->trigger();
        }
        $usercontext = context_user::instance($usernew->id);

        // If check the parent role assign or not.
        if ($roleid = get_config('auth_magic', 'owneraccountrole')) {
            role_assign($roleid, $USER->id, $usercontext->id);
        }
    } else {
        $usernew = $existuser;
        $usercreated = true;
    }
    $accessauthtoall = get_config('auth_magic', 'authmethod');
    if ($accessauthtoall || $usernew->auth == 'magic') {
        if ($coursebase) {
            // Enroll to user in course.
            $enrolstauts = auth_magic_enroll_course_user($coursebase, $usernew, $enrolmentduration);
        }
        $auth = get_auth_plugin('magic');
        // Request login url.
        $auth->create_magic_instance($usernew);
        \core\session\manager::gc(); // Remove stale sessions.
        $returnparams = array('userid' => $usernew->id,
        'return' => $usercreated, 'courseid' => $courseid);
        if ($coursebase) {
            $returnparams['coursebase'] = $coursebase;
        }
        if ($existuser) {
            $returnparams['userexist'] = true;
        }
        redirect(new moodle_url('/auth/magic/registration.php', $returnparams));
    }
    redirect(new moodle_url('/auth/magic/registration.php'), get_string('quickregisterfornonauth', 'auth_magic'),
        null, \core\output\notification::NOTIFY_INFO);
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('quickregistration', 'auth_magic'));
$form->display();
echo $OUTPUT->footer();
