<?php
/**
 * Created by Hito.
 * Date: 05.04.2020
 * Time: 15:45
 * VK: https://vk.com/igordux
 */

class Config
{
    public static $DB = array(
        'host' => 'localhost',
        'user' => 'root',
        'pwd' => '&^bujhtrgbljhfc',
        'name' => 'AnonymBot',
        'tables' => array(
            'users'=> 'cf_users',
            'messages' => 'cf_messages',
            'responses' => 'cf_responses',
            'posts' => 'cf_posts'
        )
    );

    public static $group = array(
        'id' => '184099642',
        'token' => '5acbf5ac442dd8c101e7674f3e49c9a7488a86c94c1fb3e904d415a7831164f693b121866008ca0be3b9d',
        'secret' => 'GIUG828h923ias2kasd',
        'confirmation' => 'a36a1b10',

    );

    public static $hackerman = 'WTF ты тут забыл, катись колбаской';
}