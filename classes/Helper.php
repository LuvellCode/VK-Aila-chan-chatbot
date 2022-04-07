<?php
/**
 * Created by Hito.
 * Date: 05.04.2020
 * Time: 16:33
 * VK: https://vk.com/igordux
 */

class Helper
{
    public static function exst(&$var = null) {
        return isset($var) ? $var: "";
    }

    public static function checkCmd($message, $cmds=array()){
        foreach ($cmds as $cmd){
            if (preg_match("/^{$cmd}(.|\W{1,2}){0,2}$/iu", $message)) return true;
        }
        return false;
    }

    public static function post($url = null, $data = array(), $cookie = null) {
        $ch = curl_init();

        $headers = array(
            "accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "accept-language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
            "user-agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.3.2924.87 Safari/537.36"
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if(isset($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if(isset($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        $response = curl_exec($ch);

        preg_match_all("/Set-Cookie: (.*?);/", $response, $cookie);

        $content = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $header = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

        return array(
            "content" => $content,
            "header"  => $header,
            "cookie"  => implode(";", $cookie[1])
        );
    }

    public static function curl($url = null, $data = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(isset($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        return curl_exec($ch);
    }

    public static function api($method = null, $data = null) {
        global $db;

        $captcha_sid = self::exst($GLOBALS["_POST"]["csid"]);
        $captcha_key = self::exst($GLOBALS["_POST"]["ckey"]);

        if($captcha_sid && $captcha_key) {
            $data["captcha_sid"] = $captcha_sid;
            $data["captcha_key"] = $captcha_key;
        }

        $data["v"]            = 5.69;
        $data["group_id"]     = Config::$group['id'];
        $data["access_token"] = Config::$group['token'];

        $response = self::curl("https://api.vk.com/method/$method", $data);

        if(strlen($response) == 0) {
            return self::api($method, $data);
        }

        $decode = json_decode(
            $response,
            true
        );

        if(!isset($decode["response"])) {
            $db->insert(Config::$DB['tables']['responses'], array(
                "response" => $response,
                "date"     => date("d.m.Y H:i:s")
            ));

            if(Helper::exst($decode["error"]["error_code"]) == 14) {
                $postfields = array();

                foreach ($data as $key => $value) {
                    $postfields[] = "$key=$value";
                }

                $GLOBALS["captcha"]->insert($decode["error"]["captcha_sid"], $decode["error"]["captcha_img"], implode("&", $postfields));
            }
        }

        if(self::exst($decode["error"]["error_code"]) == 6) {
            usleep(rand(100, 999));

            return self::api($method, $data);
        }

        return $decode;
    }

    public static function get_user_name($user_id = null) {
        $get_user = self::api("users.get", array(
            "user_id" => $user_id,
            "lang"    => "ru"
        ));
        $get_user_response = self::exst($get_user["response"][0]);

        return $get_user_response["first_name"]." ".$get_user_response["last_name"];
    }

    public static function endings($number = null, $endings = array()) {
        $num100 = $number % 100;
        $num10 = $number % 10;
        if ($num100 >= 5 && $num100 <= 20) {
            return $endings[0];
        } else if ($num10 == 0) {
            return $endings[0];
        } else if ($num10 == 1) {
            return $endings[1];
        } else if ($num10 >= 2 && $num10 <= 4) {
            return $endings[2];
        } else if ($num10 >= 5 && $num10 <= 9) {
            return $endings[0];
        } else {
            return $endings[2];
        }
    }

    public static function send_message($user_id=null, $message=null, $title=null, $attachment=null, $keyboard=null) {

        return self::api("messages.send", array(
            "user_id"      => $user_id,
            "message"      => $message,
            "title"        => $title,
            "attachment"   => $attachment,
            "keyboard"     => $keyboard,
        ));
    }

    public static function generateButtons(array $buttons, $inline = false, $one_time = false) {
        if ($inline) $one_time = false;
        $array = [
            'one_time' => $one_time,
            'inline' => $inline,
            'buttons' => [],
        ];
        foreach ($buttons as $btn){

            $array['buttons'][$btn['row']][] = [
                'action' => [
                    'type' => $btn['type'],
                    'payload' => json_encode($btn['payload'], JSON_UNESCAPED_UNICODE),
                    'label' => $btn['label'],
                ],
                'color' => $btn['color'],
            ];
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public static function getKeyboard($type, $inline = false, $one_time = false) {
        //Main buttons
        $start = [
            'row' => '0',
            'type' => 'text',
            "payload" => [
                "button" => "start"
            ],
            "label" => "Начать ⭐",
            "color" => "positive"
        ];
        $settings = [
            'row' => '2',
            'type' => 'text',
            "payload" => [
                "button" => "settings"
            ],
            "label" => "Настройки ⚙️",
            "color" => "secondary"
        ];
        $stats = [
            'row' => '2',
            'type' => 'text',
            "payload" => [
                "button" => "stats"
            ],
            "label" => "Статистика 📊",
            "color" => "secondary"
        ];
        $stop = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "stop"
            ],
            "label" => "Стоп 🛑",
            "color" => "negative"
        ];
        $explore = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "explore"
            ],
            "label" => "Узнать собеседника 🧐",
            "color" => "primary"
        ];
        $top = [
            'row' => '2',
            'type' => 'text',
            "payload" => [
                "button" => "stats"
            ],
            "label" => "Топ 👑",
            "color" => "secondary"
        ];
        $online = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "stats"
            ],
            "label" => "Онлайн 💎",
            "color" => "secondary"
        ];

        $close = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "close"
            ],
            "label" => "Закрыть клавиатуру",
            "color" => "secondary"
        ];
        $open = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "open"
            ],
            "label" => "Открыть клавиатуру",
            "color" => "secondary"
        ];

        //Settings buttons
        $settings_btns = [];
        $cancel = [
            'row' => '3',
            'type' => 'text',
            "payload" => [
                "button" => "cancel"
            ],
            "label" => "Закрыть настройки",
            "color" => "negative"
        ];

        for ($i=1;$i<=5;$i++){
            $label = (($i==2||$i==4)?"Женский":(($i==1||$i==3)?"Мужской":"Любой"));
            $row = (($i<=2)?'0':'1');
            $settings_btns[$i] = [
                'row' => $row,
                'type' => 'text',
                "payload" => [
                    "button" => "settings_{$i}"
                ],
                "label" => "{$i} - {$label}",
                "color" => "primary"
            ];
        }

        switch ($type){
            case "help":
                return self::generateButtons([$start, $settings, $stats, $top, $online, $close], $inline, $one_time);
                break;
            case "stop":
                $stop['row'] = '0';
                $close['row'] = '0';
                return self::generateButtons([$stop, $close], $inline, $one_time);
                break;
            case "close":
                return self::generateButtons([$open], $inline, true);
                break;
            case "clear":
                return self::generateButtons([], $inline, true);
                break;
            case "dialog":
                $stop['row'] = '0';
                $explore['row'] = '1';
                $close['row'] = '0';
                return self::generateButtons([$explore, $stop, $close], $inline, $one_time);
                break;
            case "settings":
                $settings_btns[] = $cancel;
                return self::generateButtons($settings_btns, $inline, $one_time);
                break;
        }
        return null;
    }
}