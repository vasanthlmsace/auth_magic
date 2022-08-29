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
 * Magic authentication define js.
 * @module   auth_magic
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'core/fragment', 'core/modal_factory', 'core/modal_events', 'core/notification', 'core/str'],
 function($, Fragment, ModalFactory, ModalEvents, notification, String) {

    /**
     * Controls Custom styles tool action.
     * @param {object} params
     */
    var AuthMagic = function(params) {
        var self = this;
        if (params.enrolstatus !== undefined) {
            self.displayAuthInfoBox(params);
        }
        if (params.cancopylink !== undefined) {
            self.copyuserLoginlink(params.cancopylink);
        }
        if (params.loginhook) {
            self.magicLoginHook(params);
        }
        if (params.hascourseregister) {
            self.courseQuickRegistration(params);
        }
        return true;
    };

    AuthMagic.prototype.courseQuickRegistration = function(params) {
        var uniqueid = "user-index-participants-" + params.courseid;
        var handleSelector = ".pagelayout-incourse [data-table-uniqueid=" + uniqueid + "]";
        var addHandler = document.querySelectorAll(handleSelector);
        if (addHandler) {
            var singleButton = document.createElement("div");
            singleButton.setAttribute("class", "singlebutton quickregister-button");

            // Create a form.
            var form = document.createElement("form");
            form.setAttribute("method", "get");
            form.setAttribute("action", params.url);
            form.setAttribute("id", "quickregister-button");

            // Create an input element for Full Name
            var courseblock = document.createElement("input");
            courseblock.setAttribute("type", "hidden");
            courseblock.setAttribute("name", "courseid");
            courseblock.setAttribute("value", params.courseid);

            var submit = document.createElement("input");
            submit.setAttribute("type", "submit");
            submit.setAttribute("value", params.strquickregister);
            submit.setAttribute("class", "btn btn-secondary my-1");

            form.appendChild(courseblock);
            form.appendChild(submit);
            singleButton.appendChild(form);
            addHandler[0].appendChild(singleButton);
        }
    };

    AuthMagic.prototype.magicLoginHook = function(params) {
        var authSelector = "#page-login-index .potentialidplist a[title=\"" + params.strbutton + "\"]";
        var getMagicLink = document.querySelectorAll(authSelector)[0];
        var potentialiDp = $("#page-login-index .potentialidplist").prev();
        var potentialiDpList = document.querySelectorAll("#page-login-index .potentialidplist .potentialidp");
        if (getMagicLink === undefined) {
            authSelector = "#page-login-index .login-identityproviders a";
            var getMagicLinks = document.querySelectorAll(authSelector);
            if (getMagicLinks.length) {
                getMagicLinks.forEach(function(item) {
                    var inner = item.innerHTML.trim();
                    if (inner == params.strbutton) {
                        getMagicLink = item;
                        potentialiDpList = document.querySelectorAll("#page-login-index .login-identityproviders a");
                        potentialiDp = document.querySelectorAll("#page-login-index .login-identityproviders h2")[0];
                    }
                });
            }
        }
        if (getMagicLink) {
            getMagicLink.classList.remove("btn-secondary");
            getMagicLink.classList.add("btn-primary");
            var userNameBlock = document.querySelectorAll("#page-login-index form#login .form-group")[0];
            if (userNameBlock) {
                userNameBlock.appendChild(getMagicLink);
                // Create a span.
                var span = document.createElement("span");
                span.setAttribute("class", "magic-password-instruction");
                var passInfo = String.get_string('passinfo', 'auth_magic');
                $.when(passInfo).done(function(localizedEditString) {
                    span.innerHTML = localizedEditString;
                });
                userNameBlock.appendChild(span);
            }
            if (potentialiDpList.length <= 1) {
                $(potentialiDp).hide();
                    var identityProvider = document.querySelectorAll("#page-login-index .login-identityproviders")[0];
                    if (identityProvider) {
                        $(identityProvider).prev().hide();
                        $(identityProvider).next().hide();
                    }
            }
            getMagicLink.addEventListener("click", function(e) {
                e.preventDefault();
                var returnurl = e.currentTarget.getAttribute("href");
                var userEmail = "";
                var mailSelector = document.querySelectorAll("form#login #username")[0];
                if (mailSelector) {
                    userEmail = mailSelector.value;
                }
                // Create a form.
                var form = document.createElement("form");
                form.setAttribute("method", "post");
                form.setAttribute("action", returnurl);
                form.setAttribute("id", "magic-login-form");

                    // Create an input element for Full Name
                var magicLogin = document.createElement("input");
                magicLogin.setAttribute("type", "hidden");
                magicLogin.setAttribute("name", "magiclogin");
                magicLogin.setAttribute("value", 1);

                var email = document.createElement("input");
                email.setAttribute("type", "hidden");
                email.setAttribute("name", "usermail");
                email.setAttribute("value", userEmail);

                form.appendChild(magicLogin);
                form.appendChild(email);

                getMagicLink.parentNode.appendChild(form);

                var magicForm = document.querySelectorAll("form#magic-login-form")[0];
                magicForm.submit();
            });
        }
    };

    AuthMagic.prototype.copyuserLoginlink = function(cancopylink) {
        var self = this;
        if (cancopylink) {
            var invitationlink = document.querySelectorAll("table.magicinvitationlink .magic-invitationlink");
            if (invitationlink) {
                invitationlink.forEach(function(items) {
                    items.addEventListener('click', function(e) {
                        e.preventDefault();
                        var userlogin = e.currentTarget.getAttribute("data-invitationlink");
                        self.copyText(userlogin);
                    });
                });
            }
        }
    };

    AuthMagic.prototype.copyTextCliboard = function() {
        var self = this;
        var copyTextBlock = document.querySelectorAll(".auth-magic-block #copy-text")[0];
        if (copyTextBlock) {
            self.copyText(copyTextBlock.value, true);
            copyTextBlock.select();
        }
    };

    AuthMagic.prototype.copyText = function(copytext, modal = false) {
        if (typeof (navigator.clipboard) == 'undefined') {
            var textArea = document.createElement("textarea");
            textArea.value = copytext;
            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            if (!modal) {
                document.body.appendChild(textArea);
            } else {
                document.querySelectorAll(".modal .modal-content")[0].appendChild(textArea);
            }
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                return false;
            }
            if (!modal) {
                document.body.removeChild(textArea);
            } else {
                document.querySelectorAll(".modal .modal-content")[0].removeChild(textArea);
            }
        } else {
            navigator.clipboard.writeText(copytext);
        }
        return true;
    };

    AuthMagic.prototype.displayAuthInfoBox = function(params) {
        var self = this;
        ModalFactory.create({
            title: params.strconfirm,
            type: ModalFactory.types.CANCEL,
            body: self.getAuthMagicBody(params),
            large: true
        }).then(function(modal) {
            modal.show();
            modal.getRoot().on(ModalEvents.bodyRendered, function() {
                var copyBoardButton = document.querySelectorAll(".auth-magic-block .copy-link-block #copy-cliboard")[0];
                if (copyBoardButton) {
                    copyBoardButton.addEventListener("click", self.copyTextCliboard.bind(self));
                }
            });
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
                window.open(params.returnurl, '_self');
            });
            return modal;
        }).catch(notification.exception);
    };

    AuthMagic.prototype.getAuthMagicBody = function(params) {
        return Fragment.loadFragment('auth_magic', 'display_box_content', params.contextid, params);
    };

    return {
        init: function(params) {
            return new AuthMagic(params);
        }
    };

 });