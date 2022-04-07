<?php
ini_set('display_errors', 1);
usleep(rand(100, 999));

require_once("inc/autoloader.php");

$_POST = json_decode(
	file_get_contents("php://input"),
	true
);

if ($_POST['group_id'] != Config::$group['id'] or $_POST['secret'] != Config::$group['secret']) {
    header("Location: https://lmgtfy.com/?q=Гей+Порно+Онлайн");
    die(Config::$hackerman);
}


$db = new DB(Config::$DB['host'], Config::$DB['user'], Config::$DB['pwd'], Config::$DB['name']);


$type = Helper::exst($_POST["type"]);
$user_id = (int) Helper::exst($_POST["object"]["user_id"]);

if($db->num_rows($db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `user_id` = '".$user_id."'")) == 0) {
	$db->insert(Config::$DB['tables']['users'], array(
		"user_id"   => $user_id,
		"user_name" => Helper::get_user_name($user_id),
		"talking"   => -2
	));
}

$assoc = $db->assoc($db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `user_id` = '".$user_id."'"));

switch ($type) {
	case "confirmation": die(Config::$group['confirmation']);
	case "message_new":
		$message = Helper::exst($_POST["object"]["body"]);
		$message_id = (int) $_POST["object"]["id"];

		if($db->num_rows($db->query("SELECT * FROM `".Config::$DB['tables']['messages']."` WHERE `message_id` = '".$message_id."'")) == 0 || isset($_POST["skip_db_limit"])) {
            if($assoc["group_member"] == 0) {
                Helper::send_message($user_id, Messages::$not_member);
                die('ok');
            }

			if(!isset($_POST["skip_db_limit"])) {
				$db->insert(Config::$DB['tables']['messages'], array(
					"message_id" => $message_id
				));
			}



			if ($assoc['settings_waiting'] == 1) {
                if($assoc["talking"] == -2) {
                    $payload = json_decode($_POST['object']['payload'], true);
                    if (substr($payload['button'], 0, 8) == 'settings') {
                        $choice = substr($payload['button'], -1, 1);
                        $user_sex = ($choice == 1 || $choice == 2) ? $choice : $assoc["user_sex"];
                        $user_sex_choose = ($choice == 3 || $choice == 4 || $choice == 5) ? $choice - 2 : $assoc["user_sex_choose"];

                        $db->query("UPDATE `" . Config::$DB['tables']['users'] . "` SET `user_sex` = $user_sex, `user_sex_choose` = $user_sex_choose WHERE `user_id` = '" . $user_id . "'");
                        Helper::send_message($user_id, Messages::$stngs_saved);
                    } else {
                        $db->query("UPDATE `" . Config::$DB['tables']['users'] . "` SET `settings_waiting` = 0 WHERE `user_id` = '" . $user_id . "'");
                        Helper::send_message($user_id, Messages::$stngs_not_saved, null, null, Helper::getKeyboard('help'));
                    }
                    die('ok');
                }
            }
            $attachments_all = array();
            if($attachments = Helper::exst($_POST["object"]["attachments"])) {
                for($i = 0; $i < count($attachments); $i++) {
                    $attachment = $attachments[$i];
                    $type = $attachment["type"];
                    $attach_info = $attachment[$type];

                    $owner_id = $attach_info["owner_id"];
                    $attach_id = $attach_info["id"];
                    $access_key = $attach_info["access_key"];

                    if($type == "photo" or $type == "sticker") {
                        $save_path = "photos/".time()."_{$user_id}_{$attach_id}.jpg";

                        if ($type == "sticker"){
                            $type = "photo";
                            $url = $attachment[$attachment["type"]]["photo_128"];
                            /*Размеры стикеров
                             * 512; 256; 128; 64
                            */

                            $save_path = "photos/".time()."_{$user_id}_{$attach_id}.png";
                            if (!file_exists($save_path)){
                                file_put_contents($save_path, file_get_contents($url)) or die(json_encode(error_get_last()));
                            }
                        }
                        else if ($type == "photo"){
                            file_put_contents($save_path, file_get_contents(Helper::exst($attach_info["photo_604"]))) or die(json_encode(error_get_last()));
                        }

                        $get_server = Helper::api("photos.getMessagesUploadServer");
                        print "Photo!";
                        var_dump($get_server);

                        if($upload_url = Helper::exst($get_server["response"]["upload_url"])) {
                            $upload = json_decode(
                                Helper::curl($upload_url, array(
                                    "photo" => new CurlFile($save_path)
                                )),
                                true
                            );

                            $server = Helper::exst($upload["server"]);
                            $photo = Helper::exst($upload["photo"]);
                            $hash = Helper::exst($upload["hash"]);

                            if($server && $photo && $hash) {
                                $save_photo = Helper::api("photos.saveMessagesPhoto", array(
                                    "server"    => $server,
                                    "photo"     => $photo,
                                    "hash"      => $hash,
                                ));

                                if($response = Helper::exst($save_photo["response"][0])) {
                                    $attachments_all[] = "{$type}".$response["owner_id"]."_".$response["id"];
                                }
                            }
                        }
                        chmod($save_path, 0777);
                        unlink($save_path);
                    }
                    else if ($type == "doc") {
                        print "doc!\n";

                        $url = $attach_info["url"];
                        $save_path = "photos/{$attach_info["title"]}.{$attach_info["ext"]}";

                        file_put_contents($save_path, file_get_contents($url)) or die(json_encode(error_get_last()));

                        if ($attach_info['title'] == 'voice_message.webm') {
                            $get_server = Helper::api("docs.getMessagesUploadServer",
                                [
                                    "peer_id" => $assoc['talking'],
                                    "type" => "audio_message"
                                ]
                            );
                        }
                        else {
                            $get_server = Helper::api("docs.getMessagesUploadServer",
                                [
                                    "peer_id" => $assoc['talking'],
                                    "type" => "doc"
                                ]
                            );
                        }

                        if($upload_url = Helper::exst($get_server["response"]["upload_url"])) {
                            $file = json_decode(
                                Helper::curl($upload_url, array(
                                    "file" => new CurlFile($save_path)
                                )),
                                true
                            )["file"];


                            if($file) {
                                $save = Helper::api("docs.save", array(
                                    "file"      => $file,
                                    "title"     => $attach_info["title"],
                                ));

                                if($response = Helper::exst($save["response"][0])) {
                                    $attachments_all[] = "doc".$response["owner_id"]."_".$response["id"];
                                }
                            }
                        }
                        chmod($save_path, 0777);
                        unlink($save_path);

                    }
                    else {
                        if ($type == "audio") {
                            //$url = $attachment[$attachment["type"]]["url"];
                            //$artist = $attachment[$attachment["type"]]["artist"];
                            //$title = $attachment[$attachment["type"]]["title"];
                            //$save_path = "photos/{$artist} - {$title}.mp3";
                            //file_put_contents($save_path, file_get_contents($url)) or die(json_encode(error_get_last()));
                        }
                        $doc_full = "{$type}{$owner_id}_{$attach_id}". ((isset($access_key)) ? "_{$access_key}" : "");
                        $attachments_all[] = $doc_full;
                    }
                }
            }


            if      (Helper::checkCmd($message, ['стоп', 'stop'])) {
                if($assoc["talking"] == -2) {
                    Helper::send_message($user_id, Messages::$cant_stop);
                    die("ok");
                }
                $talking = -2;
            }
            else if (Helper::checkCmd($message, ['узнать собеседника'])) {
                if ($assoc['settings_waiting'] == 2) {
                    Helper::send_message($user_id, Messages::explored_already($assoc['talking']));
                }
                else if ($assoc['settings_waiting'] == 1) {
                    Helper::send_message($user_id, Messages::$user_explore_already);
                }
                else {
                    $companion = $db->assoc($db->query("SELECT * FROM `" . Config::$DB['tables']['users'] . "` WHERE `user_id` = '" . $assoc['talking'] . "'"));
                    if ($companion['settings_waiting']) {
                        Helper::send_message($user_id, Messages::companion_explore_accept($assoc['talking']));
                        Helper::send_message($assoc['talking'], Messages::companion_explore_accept($user_id));
                        $db->query("UPDATE `" . Config::$DB['tables']['users'] . "` SET `settings_waiting` = 2 WHERE `user_id` = '{$user_id}' OR `user_id` = '{$assoc['talking']}'");
                    } else {
                        $db->query("UPDATE `" . Config::$DB['tables']['users'] . "` SET `settings_waiting` = 1 WHERE `user_id` = '" . $user_id . "'");
                        Helper::send_message($user_id, Messages::$user_explore);
                        Helper::send_message($assoc['talking'], Messages::$companion_explore);
                    }
                }
                die('ok');
            }
            else if (Helper::checkCmd($message, ['закрыть клавиатуру'])) {
                Helper::send_message($user_id, Messages::$key_close, null, null, Helper::getKeyboard('close'));
                die('ok');
            }
            else if (Helper::checkCmd($message, ['клавиатура', 'открыть клавиатуру'])) {
                if($assoc["talking"]==-2) {
                    Helper::send_message($user_id, Messages::$key_open, null, null, Helper::getKeyboard('help'));
                }
                else if($assoc["talking"]==-1) {
                    Helper::send_message($user_id, Messages::$key_open, null, null, Helper::getKeyboard('stop'));
                }
                else if($assoc["talking"]>=1) {
                    Helper::send_message($user_id, Messages::$key_open, null, null, Helper::getKeyboard('dialog'));
                }
                die('ok');
            }
            else if ($assoc["talking"] >= 1) {
                Helper::send_message($assoc["talking"], $message, "Собеседник", implode(",", $attachments_all));

                $db->update(Config::$DB['tables']['users'], array(
                    "sent_messages:+" => 1
                ), array(
                    "user_id"         => $assoc["user_id"]
                ));
            }
            else if (Helper::checkCmd($message, ['start', 'старт', 'начать'])) {
                if($assoc["user_sex"] == 0 || $assoc["user_sex_choose"] == 0) {
                    Helper::send_message($user_id, Messages::$stngs_need);
                    die("ok");
                }

                $search_by_sex = $assoc["user_sex_choose"] != 3 ? " AND `user_sex` = {$assoc["user_sex_choose"]}": "";
                $search_person = $db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `user_id` != '".$user_id."' AND `talking` = -1 AND (`user_sex_choose` = ".$assoc["user_sex"]." OR `user_sex_choose` = 3) AND `group_member` = 1".$search_by_sex." ORDER BY RAND()");


                if($db->num_rows($search_person) == 0) {
                    $talking = -1;
                }
                else {
                    $assoc_person = $db->assoc($search_person);

                    $db->update(Config::$DB['tables']['users'], array(
                        "talking" => $assoc_person["user_id"]
                    ), array(
                        "user_id" => $assoc["user_id"]
                    ));
                    $db->update(Config::$DB['tables']['users'], array(
                        "talking" => $assoc["user_id"]
                    ), array(
                        "user_id" => $assoc_person["user_id"]
                    ));


                    Helper::send_message($assoc["user_id"], Messages::$connected,null, null,
                        Helper::getKeyboard('dialog', false, false));
                    usleep(rand(100, 999));
                    Helper::send_message($assoc_person["user_id"], Messages::$connected,null, null,
                        Helper::getKeyboard('dialog', false, false));
                }
            }
            else if (Helper::checkCmd($message, ['settings', 'настройки'])) {
                Helper::send_message($user_id, Messages::stngs($assoc), null, null, Helper::getKeyboard('settings', false, false));
                $db->query("UPDATE `".Config::$DB['tables']['users']."` SET `settings_waiting` = 1 WHERE `user_id` = '".$user_id."'");
                die("ok");
            }
            else if (Helper::checkCmd($message, ['online','онлайн'])) {
                $users = array();
                $users_online = 0;

                $query = $db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `group_member` = 1");

                while ($assoc = $db->assoc($query)) {
                    $users[] = $assoc["user_id"];
                }

                $get_users = Helper::api("users.get", array(
                    "user_ids" => implode(",", $users),
                    "fields"   => "online"
                ));
                $get_users_response = $get_users["response"];

                for ($i = 0; $i < count($get_users_response); $i++) {
                    if(isset($get_users_response[$i]["online"]) && $get_users_response[$i]["online"] == 1) {
                        $users_online++;
                    }
                }

                $users_searching = $db->num_rows($db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `talking` = -1 AND `group_member` = 1"));

                Helper::send_message($user_id, "Сейчас в сети ".$users_online." ".Helper::endings($users_online, array("человек", "человек", "человека")).", ".$users_searching." из них ищет собеседника.");
                die("ok");
            }
            else if (Helper::checkCmd($message, ['stats', 'statistic', 'статы', 'статистика'])) {
                Helper::send_message($user_id, "Всего ты отправил".(($assoc["user_sex"] == 2) ? "a": "")." ".$assoc["sent_messages"]." ".Helper::endings($assoc["sent_messages"], array("сообщений", "сообщение", "сообщения"))." &#128202;");
                die("ok");
            }
            else if (Helper::checkCmd($message, ['top', 'топ'])) {
                $top_message = "ТОП 3 самых общительных участника:\n\n";

                $users = array();

                $query = $db->query("SELECT * FROM `".Config::$DB['tables']['users']."` WHERE `user_id` ORDER BY `sent_messages` DESC LIMIT 3");

                for($i = 0; $i < $db->num_rows($query); $i++) {
                    $assoc_user = $db->assoc($query);

                    $top_message .= (($i == 0) ? "🥇": "") . (($i == 1) ? "🥈": "") .(($i == 2) ? "🥉": "");
                    $top_message .= " [id".$assoc_user["user_id"]."|".$assoc_user["user_name"]."] -- отправил".(($assoc_user["user_sex"] == 2) ? "a": "")." ".$assoc_user["sent_messages"]." ".Helper::endings($assoc_user["sent_messages"], array("сообщений", "сообщение", "сообщения")).".\n";
                }

                Helper::send_message($user_id, $top_message);
                die("ok");
            }
            else if (Helper::checkCmd($message, ['help', 'помощь', 'хелп'])) {
                Helper::send_message($user_id, Messages::$help,null, null, Helper::getKeyboard('help', false, false));
                die("ok");
            }

			if($assoc["talking"] == -1 && $assoc["talking"] == Helper::exst($talking)) {
                Helper::send_message($assoc["user_id"], Messages::$search_already);
				die("ok");
			}

			if($assoc["talking"] == Helper::exst($talking)) unset($talking);

			if(isset($talking)) {
				$db->query("UPDATE `".Config::$DB['tables']['users']."` SET `talking` = $talking WHERE `user_id` = '".$user_id."'");
				$type = 'help';

				if($talking == -1) {
					$message_result = "Ты добавлен".(($assoc["user_sex"] == 2) ? "a": "")." в очередь поиска, подожди немного, мы скоро найдём тебе собеседника.";
					$type = 'stop';
				} else if($talking == -2 && $assoc["talking"] >= 1) {
					$message_result = "Ты остановил".(($assoc["user_sex"] == 2) ? "a": "")." диалог и покинул".(($assoc["user_sex"] == 2) ? "a": "")." своего собеседника.";
                    $db->query("UPDATE `" . Config::$DB['tables']['users'] . "` SET `settings_waiting` = 0 WHERE `user_id` = '{$user_id}' OR `user_id` = '{$assoc['talking']}'");

					$db->query("UPDATE `".Config::$DB['tables']['users']."` SET `talking` = $talking WHERE `user_id` = '".$assoc["talking"]."'");

                    Helper::send_message($assoc["talking"], Messages::$companion_left, null, null,
                        Helper::getKeyboard('help', false, false));
				} else if($talking == -2) {
					$message_result = Messages::$search_stop;
				}

                Helper::send_message($user_id, $message_result, null, null,
                    Helper::getKeyboard($type, false, false));
			}
		}

		break;
	
	case "group_leave":

	    if ($assoc['talking'] >= 1) {
            Helper::send_message($assoc["talking"], Messages::$companion_left, null, null,
                Helper::getKeyboard('help', false, false));
            $db->update(Config::$DB['tables']['users'], ["talking" => -2], ["user_id" => $assoc["talking"]]);
        }
	
		$db->update(Config::$DB['tables']['users'], array(
			"group_member" => 0,
            "talking" => -2
		), array(
			"user_id" => $user_id
		));

		Helper::send_message($user_id, Messages::$leave, null, null, Helper::getKeyboard('clear'));

		break;

	case "group_join":
	
		$db->update(Config::$DB['tables']['users'], array(
			"group_member" => 1
		), array(
			"user_id" => $user_id
		));

		Helper::send_message($user_id, Messages::$join, null, null, Helper::getKeyboard('help', false, false));
		//send_message($admin_id, "[id$user_id|".$assoc["user_name"]."] вступил".(($assoc["user_sex"] == 2) ? "a": "")." в сообщество.");

		break;

	default:
		break;
}

echo "ok";