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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/auth/magic/lib.php');

if (!is_enabled_auth('magic')) {
    throw new moodle_exception(get_string('pluginisdisabled', 'auth_magic'));
}

$userid = optional_param('userid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$suspend = optional_param('suspend', 0, PARAM_INT);
$unsuspend = optional_param('unsuspend', 0, PARAM_INT);
$sendlink = optional_param('sendlink', 0, PARAM_INT);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 15, PARAM_INT);        // How many per page.
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$acl          = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
require_login();
$url = new moodle_url('/auth/magic/listusers.php', array('userid' => $userid));
$PAGE->set_url($url);
$sitecontext = context_system::instance();
$strlistmagickeys = get_string('userkeyslist', 'auth_magic');
if (!$userid) {
    $sitecontext = context_system::instance();
    require_capability("auth/magic:viewloginlinks", $sitecontext);
    $PAGE->set_context($sitecontext);
    if (is_siteadmin()) {
        $PAGE->set_pagelayout('admin');
        admin_externalpage_setup('auth_magic_loginlinks');
    } else {
        if ($profilenode = $PAGE->settingsnav->find('myprofile', null)) {
            $profilenode->make_active();
        }
        $PAGE->navbar->add($strlistmagickeys, $url);
        $PAGE->set_title("$SITE->fullname: ". $strlistmagickeys);
        $PAGE->set_heading($SITE->fullname);
    }
} else {
    $context = context_user::instance($USER->id);
    if (!auth_magic_is_parent_see_child_magiclinks()) {
        $capabilityname = get_capability_string("auth/magic:viewchildloginlinks");
        throw new moodle_exception('nopermissions', '', '', $capabilityname);
    }
    $title = fullname($USER).": $strlistmagickeys";
    $PAGE->set_context($context);
    $PAGE->set_title($title);
    // If user is logged in, then use profile navigation in breadcrumbs.
    if ($profilenode = $PAGE->settingsnav->find('myprofile', null)) {
        $profilenode->make_active();
    }
    $PAGE->navbar->add($strlistmagickeys, $url);
}

$site = get_site();
$stredit   = get_string('edit');
$strcopy = get_string('copyinvitationlink', 'auth_magic');
$strsend = get_string('sendlink', 'auth_magic');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$returnparams = array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page' => $page);
if ($userid) {
    $returnparams['userid'] = $userid;
}
$returnurl = new moodle_url('/auth/magic/listusers.php', $returnparams);

// Actions list.

if ($delete && confirm_sesskey()) {              // Delete a selected user, after confirmation.
    $usercontext = context_user::instance($delete);
    if ($userid) {
        require_capability('auth/magic:childuserdelete', $usercontext);
    } else {
        require_capability('auth/magic:userdelete', $sitecontext);
    }

    $user = $DB->get_record('user', array('id' => $delete, 'mnethostid' => $CFG->mnet_localhost_id), '*', MUST_EXIST);

    if ($user->deleted) {
        throw new moodle_exception('usernotdeleteddeleted', 'error');
    }
    if (is_siteadmin($user->id)) {
        throw new moodle_exception('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));

        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (delete_user($user)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($PAGE->url->out(), get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($suspend && confirm_sesskey()) {
    $usercontext = context_user::instance($suspend);
    if ($userid) {
        require_capability('auth/magic:childusersuspend', $usercontext);
    } else {
        require_capability('auth/magic:usersuspend', $sitecontext);
    }
    if ($user = $DB->get_record('user', array('id' => $suspend, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0))) {
        if (!is_siteadmin($user) && $USER->id != $user->id && $user->suspended != 1) {
            $user->suspended = 1;
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        }
    }
    redirect($returnurl);

} else if ($unsuspend && confirm_sesskey()) {
    $usercontext = context_user::instance($unsuspend);
    if ($userid) {
        require_capability('auth/magic:childusersuspend', $usercontext);
    } else {
        require_capability('auth/magic:usersuspend', $sitecontext);
    }
    if ($user = $DB->get_record('user', array('id' => $unsuspend, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
    redirect($returnurl);
} else if ($sendlink && confirm_sesskey()) {
    $usercontext = context_user::instance($sendlink);
    if ($userid) {
        require_capability('auth/magic:childusersendlink', $usercontext);
    } else {
        require_capability('auth/magic:usersendlink', $sitecontext);
    }
    // Sent invitation to user.
    if (auth_magic_sent_invitation_user($sendlink)) {
        redirect($returnurl, get_string('sentinvitationlink', 'auth_magic'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($returnurl, get_string('notsentinvitationlink', 'auth_magic'), null, \core\output\notification::NOTIFY_ERROR);
    }
}


echo $OUTPUT->header();

$columns = array('firstname', 'lastname', 'courses', 'email', 'lastaccess');

foreach ($columns as $column) {
    if ($column == 'courses') {
        continue;
    }
    $string[$column] = get_string($column);
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
                                        ['class' => 'iconsort']);

    }
    $link = "listusers.php?sort=$column&amp;dir=$columndir";
    if ($userid) {
        $link .= "&amp;userid=$userid";
    }
    $$column = "<a href=$link>".$string[$column]."</a>$columnicon";
}

// Fullname.
$a = new stdClass();
$a->firstname = 'firstname';
$a->lastname = 'lastname';
// Getting the fullname display will ensure that the order in the language file is maintained.
$fullnamesetting = get_string('fullnamedisplay', null, $a);

// Get all user name fields as an array.

$allusernamefields = [];
if (method_exists('\core_user\fields', 'get_name_fields')) {
    foreach (\core_user\fields::get_name_fields() as $field) {
        $allusernamefields[$field] = $field;
    }
} else {
    $allusernamefields = get_all_user_name_fields(false, null, null, null, true);
}
// Order in string will ensure that the name columns are in the correct order.
$usernames = order_in_string($allusernamefields, $fullnamesetting);
$fullnamedisplay = array();
foreach ($usernames as $name) {
    // Use the link from $$column for sorting on the user's name.
    $fullnamedisplay[] = ${$name};
}
 // All of the names are in one column. Put them into a string and separate them with a.
$fullnamedisplay = implode(' / ', $fullnamedisplay);
$filterfields = ['realname' => 0, 'lastname' => 1, 'firstname' => 1,  'email' => 1, 'firstaccess' => 1, 'lastaccess' => 1];
// Create the user filter form.
$ufilterparams = [];
if ($userid) {
    $ufilterparams = ['userid' => $userid];
}
$ufiltering = new user_filtering($filterfields, $PAGE->url->out(), $ufilterparams);

if ($sort == "name") {
    // Use the first item in the array.
    $sort = reset($usernames);
}


$authsql = 'auth = :auth';
$authparams = ['auth' => 'magic'];
if ($userid) {
    $childusers = auth_magic_get_parent_child_users($USER->id);
    list($usersql, $userparams) = $DB->get_in_or_equal($childusers, SQL_PARAMS_NAMED);
    $authsql .= " AND id $usersql";
    $authparams += $userparams;
}

list($filtersql, $params) = $ufiltering->get_sql_filter();
$extrasql = !empty($filtersql) ? $filtersql . "AND ". $authsql : $authsql;
$params += $authparams;
$users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '',
            $extrasql, $params, $sitecontext);
$usercount = get_users(false, '', false, null, "", '', '', '', '', '*', $authsql, $authparams);
$usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $filtersql, $params);
if ($filtersql !== '') {
    echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount ".get_string('users'));
}

// Pagination.
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $returnurl);

flush();
if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));
    $table = null;

} else {

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay;
    $table->attributes['class'] = 'magicinvitationlink generaltable table-sm';
    $table->head[] = $email;
    $table->head[] = get_string('courses');
    $table->head[] = $lastaccess;
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';
    $table->id = "users";
    foreach ($users as $user) {
        $usercontext = context_user::instance($user->id);
        $buttons = array();
        // Delete button.
        if (has_capability('auth/magic:userdelete', $sitecontext) || has_capability('auth/magic:childuserdelete', $usercontext)) {
            if (!is_mnet_remote_user($user) || $user->id != $USER->id || !is_siteadmin($user)) {
                // No deleting of self, mnet accounts or admins allowed.
                $url = new moodle_url($returnurl, array('delete' => $user->id, 'sesskey' => sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
            }
        }

        // Suspend button.
        if (has_capability('auth/magic:usersuspend', $sitecontext) || has_capability('auth/magic:childusersuspend', $usercontext)) {
            if ($user->suspended) {
                $url = new moodle_url($returnurl, array('unsuspend' => $user->id, 'sesskey' => sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/show', $strunsuspend));
            } else {
                if (!$user->id == $USER->id || !is_siteadmin($user)) {
                    $url = new moodle_url($returnurl, array('suspend' => $user->id, 'sesskey' => sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/hide', $strsuspend));
                }
            }

            if (login_is_lockedout($user)) {
                $url = new moodle_url($returnurl,  array('unlock' => $user->id, 'sesskey' => sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/unlock', $strunlock));
            }
        }

        // Edit button.
        if (has_capability('auth/magic:userupdate', $sitecontext)) {
            // Prevent editing of admins by non-admins.
            if (is_siteadmin($USER) || !is_siteadmin($user)) {
                $url = new moodle_url('/user/editadvanced.php', array('id' => $user->id, 'course' => $site->id));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));
            }
        } else if (has_capability('auth/magic:childuserupdate', $usercontext)) {
            $url = new moodle_url('/user/edit.php', array('id' => $user->id, 'returnto' => 'profile'));
            $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));
        }

        // Copy url.
        if (has_capability('auth/magic:usercopylink', $sitecontext) ||
            has_capability('auth/magic:childusercopylink', $usercontext)) {
            $invitationlink = auth_magic_get_user_invitation_link($user->id);
            $buttons[] = html_writer::link($returnurl, $OUTPUT->pix_icon('t/copy', $strcopy),
                array('class' => 'magic-invitationlink', 'data-invitationlink' => $invitationlink));
        }

        // Send link.
        if (has_capability('auth/magic:usersendlink', $sitecontext) ||
            has_capability('auth/magic:childusersendlink', $usercontext)) {
            $url = new moodle_url($returnurl, array('sendlink' => $user->id, 'sesskey' => sesskey()));
            $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/sendmessage', $strsend));
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }

        $fullname = fullname($user, true);
        $row = array ();
        $row[] = "<a href=\"../../user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>";
        $row[] = $user->email;
        $row[] = auth_magic_user_courses($user->id);
        $row[] = $strlastaccess;
        if ($user->suspended) {
            foreach ($row as $k => $v) {
                $row[$k] = html_writer::tag('span', $v, array('class' => 'usersuspended'));
            }
        }
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }
}
// Add filters.
$ufiltering->display_add();
$ufiltering->display_active();
$params = array();
$params['cancopylink'] = has_capability('auth/magic:usercopylink', $sitecontext) ||
auth_magic_is_parent_see_child_magiclinks();
$PAGE->requires->js_call_amd('auth_magic/authmagic', 'init', array($params));

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $returnurl);
}

echo $OUTPUT->footer();
