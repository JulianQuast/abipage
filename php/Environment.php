<?

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

class Environment {

    private $title = "Test";
    private $title_sep = " - ";
    private $__usernamesarr = array("activated" => null, "also_deactivated" => null);
    private $main_dir;
    private $__vars;
    private $__prefhandler;

    public function __construct() {
        $this->__prefhandler = new PreferencesHandler();
        $this->main_dir = dirname(dirname(__FILE__));
        $db = Database::getConnection();
        if ($db != null) {
            $this->__vars = array();
            $res = $db->query("SELECT * FROM " . DB_PREFIX . "preferences") or die($db->error);
            if ($res == null) {
                $prefs = new PreferencesHandler();
                $prefs->fillDBWithDefaultValues();
            }
            while ($arr = $res->fetch_array()) {
                $var = $arr["value"];
                if ($var == "false" || $var == "true") {
                    $var = $var == "true";
                }
                $this->__vars[$arr["key"]] = $var;
            }
        }
    }

    public function __get($var) {
        if (array_key_exists($var, $this->__vars)) {
//            var_dump($var, $this->__vars[$var]);
            return $this->__vars[$var];
        } else if ($var == "url") {
            return URL;
        } else if ($this->__prefhandler->hasDefault($var)) {
            return $this->__prefhandler->getDefault($var);
        }
        //return $this->{$var};
    }

    public function getUsers() {
        global $db;
        $arr = array();
        $res = $db->query("SELECT * FROM " . DB_PREFIX . "user ORDER BY last_name ASC") or die($db->error);
        while ($user = User::getFromMySQLResult($res)) {
            $arr[] = $user;
        }
        return $arr;
    }

    //HACK implement search
    public function getUserNames($also_deactivated = false, $search_string = "") {
        global $db;
        //$key = $also_deactivated ? "also_deactivated" : "activated";
        $app = "";
        if (!$also_deactivated)
            $app = " WHERE activated=1";
        if ($search_string != "") {
            $search_string_c = str_replace(" ", "%", $db->real_escape_string($search_string));
            $app .= ($app == "" ? " WHERE " : " AND ") . "(first_name LIKE \"%$search_string_c%\" OR last_name LIKE \"%$search_string_c%\") ";
        }
        //if ($this->__usernamesarr[$key] == null) {
        $arr = array();
        $res = $db->query("SELECT first_name, last_name FROM " . DB_PREFIX . "user" . $app . " ORDER BY last_name ASC") or die($db->error);
        while ($user = $res->fetch_array()) {
            $arr[] = array("first" => $user['first_name'], "last" => $user['last_name'], "both" => $user['first_name'] . " " . $user['last_name']);
        }
        //$this->__usernamesarr[$key] = $arr;
        //}
        return $arr; //$this->__usernamesarr[$key];
    }

    public function getNotActivatedUsers() {
        global $db;
        $arr = array();
        $res = $db->query("SELECT * FROM " . DB_PREFIX . "user WHERE activated=0");
        while ($user = User::getFromMySQLResult($res)) {
            $arr[] = $user;
        }
        return $arr;
    }

    public function getNotReviewedUserComments() {
        $arr = array();
        $db = Database::getConnection();
        $res = $db->query("SELECT id, text, time, commented_userid, commenting_userid FROM " . DB_PREFIX . "user_comments 
		WHERE reviewed=0 ORDER BY time DESC");
        while ($comment = $res->fetch_array()) {
            $arr[] = $comment;
        }
        return $arr;
    }

    public function getIDUsernameDictionary() {
        $arr = array();
        $db = Database::getConnection();
        $res = $db->query("SELECT id, first_name, last_name FROM " . DB_PREFIX . "user");
        while ($user = $res->fetch_array()) {
            $arr[$user["id"]] = $user["first_name"] . ' ' . $user["last_name"];
        }
        return $arr;
    }

    public function uploadImage($new_filename_wo_ext) {
        $img_types = array("jpeg", "gif", "png", "bmp", "jpg");
        if ((!empty($_FILES["uploaded_file"])) && ($_FILES['uploaded_file']['error'] == 0)) {
            $filename = basename($_FILES['uploaded_file']['name']);
            $ext = strtolower(substr($filename, strpos($filename, '.') + 1));
            //$arr = explode("/", $_FILES["uploaded_file"]["type"]);
            if (in_array($ext, $img_types) && ($_FILES["uploaded_file"]["size"] < 4000000)) {
                $newname_wo_ext = $this->main_dir . '/' . $this->upload_path . '/' . $new_filename_wo_ext;
                $newname = $newname_wo_ext . '.' . $ext;
                var_dump($newname);
                if (!file_exists($newname)) {
                    if ((move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $newname))) {
                        resizeImage($this->pic_width, $newname, $newname_wo_ext . '.' . $this->pic_format);
                        resizeImage($this->thumbnail_width, $newname, $this->main_dir . '/' . $this->upload_path . '/thumbs/' . $new_filename_wo_ext . '.' . $this->pic_format);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getLastActions($last_action_id = -1) {
        global $db;
        $actions = array();
        $res = $db->query("SELECT * FROM " . DB_PREFIX . "actions WHERE id > " . intval($last_action_id) . " ORDER BY time DESC LIMIT 0, " . $this->showed_actions) or die($db->error);
        while ($action = $res->fetch_array()) {
            $actions[] = $action;
        }
        return $actions;
    }

    public function addAction($itemid, $person, $type, $time = -1, $user = null) {
        global $db, $store;
        if ($user == null) {
            $user = Auth::getUser();
        }
        if ($time == -1) {
            $time = time();
        }
        $person = $db->real_escape_string($person);
        $type = $db->real_escape_string($type);
        $db->query("INSERT INTO " . DB_PREFIX . "actions(id, userid, itemid, person, type, time) VALUES(NULL, " . $user->getID() . ", " . intval($itemid) . ", '" . $person . "', '" . $type . "', " . $time . ")") or die($db->error);
        $store->last_action_id = $db->insert_id;
        $store->updateDB();
    }

    function sendMail($to, $topic, $text) {
        if (is_a($to, "User"))
            $to = $to->getMailAdress();
        mail($to, $topic, Markdown($text), "From: " . TITLE . "<" . ($this->system_mail_adress != "" ? $this->system_mail_adress : ("info@" . $_SERVER['HTTP_HOST'])) . ">\r\n"
                . "X-Mailer: PHP/" . phpversion() . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=ISO-8859-1\r\n");
    }

    function sendAdminMail($topic, $text) {
        User::getByMode(User::ADMIN_MODE)->sendMail($topic, $text);
    }

    function sendModeratorMail($topic, $text) {
        User::getByMode(User::MODERATOR_MODE)->sendMail($topic, $text);
    }
}