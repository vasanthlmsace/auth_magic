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
 * Strings for component 'auth_magic', language 'en'.
 *
 * @package   auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_magicdescription'] = 'Auxiliary plugin that prevents user to login into system and also discards any mail sent to the user. Can be used to <em>suspend</em> user accounts.';
$string['pluginname'] = 'Magic authentication';
$string['configtitle'] = "Magic authentication";
$string['privacy:metadata'] = 'The Magic authentication plugin does not store any personal data.';
$string['defaultenrolmentduration'] = "Default enrolment duration";
$string['defaultenrolmentrole'] = "Default enrolment role";
$string['loginexpiry'] = "Magic login link expiry";
$string['strsupportauth'] = "Supported authentication method";
$string['magiconly'] = "Magic only";
$string['anymethod'] = "Any method";
$string['strowneraccountrole'] = "Owner account role";
$string['strkeyaccount'] = "Key account";
$string['quickregistration'] = "Quick registration";
$string['magic:cansitequickregistration'] = 'Can access the site quick registration';
$string['magic:cancoursequickregistration'] = 'Can access the course quick registration';
$string['magic:viewloginlinks'] = "View the users magic login links";
$string['magic:viewchildloginlinks'] = "View the child users magic login links";
$string['magic:userdelete'] = "Delete the magic auth users";
$string['magic:usersuspend'] = "Suspend the magic auth users";
$string['magic:userupdate'] = "Updated the magic auth users";
$string['magic:usercopylink'] = "Can copy link for magic auth users";
$string['magic:usersendlink'] = "Can send link for magic auth users";
$string['magic:childuserdelete'] = "Delete the magic auth child users";
$string['magic:childusersuspend'] = "Suspend the magic auth child users";
$string['magic:childuserupdate'] = "Updated the magic auth child users";
$string['magic:childusercopylink'] = "Can copy link for magic auth child users";
$string['magic:childusersendlink'] = "Can send link for magic auth child users";
$string['getmagiclinkviagmail'] = "Get a magic link via email";
$string['courseenrolment'] = "Course enrolment";
$string['enrolmentduration'] = "Enrolment duration";
$string['invitationexpiry'] = "";
$string['invitationexpiry'] = "Magic invitation link expiry";
$string['hasbeencreated'] = "has been created";
$string['strenrolinto'] = "and enrolled into";
$string['magiclink'] = "Magic link";
$string['copyboard'] = "Copy link to cliboard";
$string['strconfirm'] = "Confirmation";
$string['userkeyslist'] = "List of magic keys";
$string['copyloginlink'] = "Copy magic login link for the user";
$string['copyinvitationlink'] = "Copy magic invitation link for the user";
$string['sendlink'] = "Send the magic link to the user";
$string['listofmagiclink'] = "List of magic keys";
$string['more'] = '{$a} more';
$string['loginsubject'] = '{$a}: Magic authentication via login';
$string['loginlinksubject'] = "Magic authentication login link";

$string['pluginisdisabled'] = 'The magic authentication plugin is disabled.';
$string['sentinvitationlink'] = "Sent the invitation link to the mail";
$string['notsentinvitationlink'] = "Doesn't sent the invitation link to the mail";
$string['emailnotexists'] = "Doesn't exist user email";
$string['sentlinktouser'] = "if the email address belongs to an account that supports login via link, a link has been sent via email.
";
$string['preventmagicauthsubject'] = "Magic authentication support information";
$string['invitationexpiryloginlink'] = "The invitation link has expired. if the email address belongs to an account that supports login via link, a link has been sent via email";
$string['loginexpiryloginlink'] = "The login link has expired. if the email address belongs to an account that supports login via link, a link has been sent via email";

$string['invitationmessage'] = 'Hi {$a->fullname},

A new account has been requested at \'{$a->sitename}\'
using your email address.

To login your new account, please click this invitation link to login directly instead username and password :

<a href=\'{$a->link}\'> {$a->link} </a> <br>

If you need help, please contact the site administrator,
{$a->admin}';


$string['loginlinknmessage'] = 'Hi {$a->fullname},

A new account has been requested at \'{$a->sitename}\'
using your email address.

To login your new account, please go to this web address login directly instead username and password :

<a href=\'{$a->link}\'> {$a->link} </a> <br>

If you need help, please contact the site administrator,
{$a->admin}';

$string['preventmagicauthmessage'] = 'Hi {$a->fullname},

A new account has been requested at \'{$a->sitename}\'
using your email address. <br>

<strong> Note: </strong> Magic link via login for only magic authentication has supported the user, so you can\'t use login via link but you have must use your password instead.

<br>

{$a->forgothtml} <br>

If you need help, please contact the site administrator,
{$a->admin}';

$string['doesnotaccesskey'] = "Doesn't have access the key in your authentication method";
$string['manualinfo'] = "Manual enrolments are not available in this course.";
$string['passinfo'] = "- or type in your password -";
$string['invailduser'] = "Invaild user";
