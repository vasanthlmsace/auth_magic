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
 * Form for editing a quick registration
 *
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package auth_magic
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/auth/magic/lib.php');

/**
 * Class quickregistration_form.
 *
 * @copyright 2022 bdecent gmbh <https://bdecent.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quickregistration_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        global $USER, $CFG, $COURSE, $PAGE;
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');
        $strcourseenrolment = get_string('courseenrolment', 'auth_magic');

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', core_user::get_property_type('id'));

        $mform->addElement('hidden', 'auth');
        $mform->setType('auth', PARAM_TEXT);

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_BOOL);
        $mform->setDefault('return', false);

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', $strgeneral);
        $stringman = get_string_manager();
        $user = new stdClass();
        $user->id = -1;
        $user->auth = "magic";
        foreach (useredit_get_required_name_fields() as $fullname) {
            $purpose = user_edit_map_field_purpose($user->id, $fullname);
            $mform->addElement('text', $fullname,  get_string($fullname),  'maxlength="100" size="30"' . $purpose);
            if ($stringman->string_exists('missing'.$fullname, 'core')) {
                $strmissingfield = get_string('missing'.$fullname, 'core');
            } else {
                $strmissingfield = $strrequired;
            }
            $mform->addRule($fullname, $strmissingfield, 'required', null, 'client');
            $mform->setType($fullname, PARAM_NOTAGS);
        }

        $purpose = user_edit_map_field_purpose($user->id, 'email');
        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"' . $purpose);
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $courses = auth_magic_get_courses_for_registration();
        $mform->addElement('header', 'moodle', $strcourseenrolment);
        if (!$courseid) {
            // Site level (System context).
            $mform->addElement('autocomplete', 'course', get_string('course'), $courses, [
                'multiple' => false
            ]);

        } else {
            $course = isset($courses[$courseid]) ? $courses[$courseid] : '';
            $mform->addElement('hidden', 'course');
            $mform->setType('course', PARAM_INT);
            $mform->setDefault('course', isset($courses[$courseid]) ? $courseid : '');

            $mform->addElement('text', 'coursename', get_string('course'), array('disabled' => true));
            $mform->setType('coursename', PARAM_TEXT);
            $mform->setDefault('coursename', $course);
            $mform->hideIf('coursename', 'course', 'eq', '');

            if (empty($course)) {
                $mform->addElement('static', 'manualinfo', '', get_string('manualinfo', 'auth_magic'));
                $mform->setType('manualinfo', PARAM_TEXT);
            }
        }
        $mform->addElement('duration', 'enrolmentduration', get_string('enrolmentduration', 'auth_magic'));
        $mform->setDefault('enrolmentduration', get_config('auth_magic', 'enrolmentduration'));
        $mform->hideIf('enrolmentduration', 'course', 'eq', '');

        $this->add_action_buttons(true, get_string('createuser'));
        $this->set_data($user);
    }

    /**
     * Extend the form definition after data has been parsed.
     */
    public function definition_after_data() {
        $mform = $this->_form;

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * Validate the form data.
     * @param array $usernew
     * @param array $files
     * @return array|bool
     */
    public function validation($usernew, $files) {
        global $DB, $CFG;
        $usernew = (object)$usernew;
        $err = [];
        if (!validate_email($usernew->email)) {
            $err['email'] = get_string('invalidemail');
        }
        if (count($err) == 0) {
            return true;
        } else {
            return $err;
        }
    }
}

