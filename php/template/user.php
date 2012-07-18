<?php
/*
 * Copyright (C) 2012 Johannes Bechberger
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function tpl_userlist($usernamearr) {
    tpl_before("user/all", "Schülerliste", "");
    tpl_item_before("", "", "userlist");
    ?>
    <?php foreach ($usernamearr as $namearr): ?>
        <?php tpl_userlink($namearr["both"]) ?>
    <?php endforeach ?>
    <?php
    tpl_item_after();
    tpl_after();
}

function tpl_user_prefs($user) {
    tpl_before("user/me/preferences");
    tpl_item_before_form(array(), "", "", "userprefs");
    ?>
    <input type="text" title="Name" placeholder="Name ([Vorname] [Nachname])" name="name" value="<?php echo $user->getName() ?>" pattern="([A-Z]([a-z](-[a-zA-Z])?)+ ?){2,3}"/><br/>
    <input type="email" title="E-Mail-Adresse" placeholder="E-Mail-Adresse" name="mail_adress" value="<?php echo $user->getMailAdress() ?>"<?php echo!Auth::isSuperAdmin() ? " readonly='readonly'" : "" ?>/><br/>
    <input type="password" title="Passwort" placeholder="Passwort" name="password"/><br/>
    <input type="password" title="Passwort wiederholen" placeholder="Passwort wiederholen" name="password_repeat"/><br/>
    <input type="password" title="Altes Passwort" placeholder="Altes Passwort" name="old_password"/><br/>
    <input type="number" name="math_course" title="Mathekursnummer" placeholder="Mathekursnummer" size="1" value="<?php echo $user->getMathCourse() ?>" min="1" max="20"/><br/>
    <input type="text" name="math_teacher" title="Mathelehrer" placeholder="Mathelehrer" value="<?php echo $user->getMathTeacher() ?>" pattern="([A-Z]([a-z](-[a-zA-Z])?)+ ?){2,3}"/><br/>
    <?php
    tpl_item_after_send("Ändern");
    tpl_after();
}

function tpl_user($user) {
    global $env;
    tpl_before("user");
    if ($user->getID() != Auth::getUserID() && $env->user_comments_editable):
        tpl_item_before_form(array(), "Kommentar schreiben", "pencil", "write_comment");
        ?>
        <textarea name="text"/><br/>
        <?php
        tpl_infobox("", "Die Kommentare müssen von einem Moderator freigeschalten werden.");
        tpl_item_after_send_anonymous();
    endif;
    foreach ($user->getUserComments($user->getID() == Auth::getUserID() || Auth::isSuperAdmin()) as $comment):
        tpl_before("", "", $comment["notified_as_bad"] ? " notifiey_as_bad" : "");
        echo $comment["text"];
        ?>
        </div>
        <div class="item-footer">
            <?php
            tpl_time_span($comment["time"]);
            tpl_user_span(Auth::isSuperAdmin() || !$comment["isanonymous"] ? $comment["commenting_userid"] : null);
            if ($user->getID() == Auth::getUserID()):
                ?>
                <span class="notify_as_bad">
                    <?php if ($comment["notified_as_bad"]) { ?>
                        <a class="sign-icon" href="?action=unnotify&id="<?php echo $comment["id"] ?>>+</a>
                    <?php } else { ?>
                        <a class="sign-icon" href="?action=notify&id="<?php echo $comment["id"] ?>>-</a>
                    <?php } ?>
                </span>
            <?php endif ?>			
        </div>
        </div>
    <?php endforeach ?>
    </div>
    <?php
    tpl_after();
}

function tpl_user_page() {
    
}

function tpl_item_after_user($time, $user) {
    ?>
    </div>
    <div class="item-footer">
        <?php tpl_time_span($time) ?>
        <?php tpl_user_span($user) ?>
    </div>
    </div>
    <?php
}