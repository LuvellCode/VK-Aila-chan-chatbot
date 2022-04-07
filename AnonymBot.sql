CREATE TABLE IF NOT EXISTS `cf_messages` (
`id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=11314 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cf_responses` (
`id` int(11) NOT NULL,
  `response` varchar(20000) NOT NULL,
  `date` varchar(20) CHARACTER SET latin1 NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1354 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cf_users` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(1000) NOT NULL,
  `user_sex` int(11) NOT NULL COMMENT '1-М | 2-Ж',
  `settings_waiting` int(11) NOT NULL COMMENT 'Настройки/Узнать собеседника. 0-Нет | 1-Да | 2-Уже узнал собеседника',
  `user_sex_choose` int(11) NOT NULL COMMENT '1-М | 2-Ж | 3-Любой',
  `talking` int(11) NOT NULL COMMENT '-2 - Не общается | -1 - В очереди',
  `sent_messages` int(11) NOT NULL,
  `group_member` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=950 DEFAULT CHARSET=utf8;

--
-- Индексы таблицы `cf_messages`
--
ALTER TABLE `cf_messages`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);

--
-- Индексы таблицы `cf_responses`
--
ALTER TABLE `cf_responses`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cf_users`
--
ALTER TABLE `cf_users`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для таблицы `cf_messages`
--
ALTER TABLE `cf_messages`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11314;
--
-- AUTO_INCREMENT для таблицы `cf_responses`
--
ALTER TABLE `cf_responses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1354;
--
-- AUTO_INCREMENT для таблицы `cf_users`
--
ALTER TABLE `cf_users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=950;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
