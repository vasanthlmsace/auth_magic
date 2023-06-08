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
 * @copyright  2023 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str'],
function(String) {

    /**
    * Controls Custom styles tool action.
    * @param {object} params
    */
    var AuthMagic = function(params) {
        var self = this;
        if (params.loginhook) {
            self.magicLoginHook(params);
        }
        if (params.customloginhook) {
            self.magicCustomLoginHook(params);
        }
        return true;
    };

    AuthMagic.prototype.magicCustomLoginHook = function(params) {
        var self = this;
        var MagicLink = self.getMagicLink(params, "#page-local-magic-login");
        if (MagicLink) {
            self.magicLoginHandler(MagicLink, "form#login #id_email");
        }
    };

    AuthMagic.prototype.getMagicLink = function(params, linkId) {
        var authSelector = linkId + " .potentialidplist a[title=\"" + params.strbutton + "\"]";
        var MagicLink = "";
        if (authSelector) {
            MagicLink = document.querySelectorAll(authSelector)[0];
            var potentialiDp = document.querySelector(linkId + " .potentialidplist");
            if (potentialiDp) {
                potentialiDp = potentialiDp.previousElementSibling;
            }
            var potentialiDpList = document.querySelectorAll(linkId + " .potentialidplist .potentialidp");
            if (MagicLink === undefined) {
                authSelector = linkId + " .login-identityproviders a";
                var MagicLinks = document.querySelectorAll(authSelector);
                if (MagicLinks.length) {
                    MagicLinks.forEach(function(item) {
                        var inner = item.innerHTML.trim();
                        if (inner == params.strbutton) {
                            MagicLink = item;
                            potentialiDpList = document.querySelectorAll(linkId + " .login-identityproviders a");
                            potentialiDp = document.querySelectorAll(linkId + " .login-identityproviders h2")[0];
                        }
                    });
                }
            }
        }
        return MagicLink;
    };

    AuthMagic.prototype.magicLoginHook = function(params) {
        var self = this;
        var MagicLink = self.getMagicLink(params, "#page-login-index");
        if (MagicLink) {
            if (!document.querySelector("#page-login-index .potentialidplist")) {
                MagicLink.classList.remove("btn-secondary");
                MagicLink.classList.add("btn-primary");
            }
            if (!params.linkbtnpos) {
                params.linkbtnpos = 0;
            }
            if (params.linkbtnpos == 0) {
                var MagicLinkBlock = document.querySelectorAll("#page-login-index form#login .form-group")[params.linkbtnpos];
                if (MagicLinkBlock) {
                    MagicLinkBlock.appendChild(MagicLink);
                    // Create a span.
                    var span = document.createElement("span");
                    span.setAttribute("class", "magic-password-instruction");
                    var passInfo = String.get_string('passinfo', 'auth_magic');
                    passInfo.done(function(localizedEditString) {
                        span.innerHTML = localizedEditString;
                    });
                    MagicLinkBlock.appendChild(span);
                }
            }
            if (params.linkbtnpos == 1) {
                var MagicLinkBlock = document.querySelectorAll("#page-login-index form#login .form-group")[params.linkbtnpos];
                if (MagicLinkBlock) {
                    MagicLinkBlock.appendChild(MagicLink);
                }
            }

            if (params.linkbtnpos <= 1 ) {
                if (potentialiDpList.length <= 1) {
                    potentialiDp.style.display = 'none';
                    var identityProvider = document.querySelectorAll("#page-login-index .login-identityproviders")[0];
                    if (identityProvider) {
                        identityProvider.previousElementSibling.style.display = 'none';
                        identityProvider.nextElementSibling.style.display = 'none';
                    }
                }
            }

            if (params.linkbtnpos == 2) {
                MagicLink.classList.remove("btn-primary");
            }
            self.magicLoginHandler(MagicLink, "form#login #username");
        }
    };

    AuthMagic.prototype.magicLoginHandler = function(MagicLink, mailHandler) {
        MagicLink.addEventListener("click", function(e) {
            e.preventDefault();
            var returnurl = e.currentTarget.getAttribute("href");
            var userValue = "";
            //form#login #username
            var mailSelector = document.querySelectorAll(mailHandler)[0];
            if (mailSelector) {
                userValue = mailSelector.value;
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

            var uservalue = document.createElement("input");
            uservalue.setAttribute("type", "hidden");
            uservalue.setAttribute("name", "uservalue");
            uservalue.setAttribute("value", userValue);

            form.appendChild(magicLogin);
            form.appendChild(uservalue);

            MagicLink.parentNode.appendChild(form);

            var magicForm = document.querySelectorAll("form#magic-login-form")[0];
            magicForm.submit();
        });
    }

    return {
        init: function(params) {
            return new AuthMagic(params);
        }
    };

});