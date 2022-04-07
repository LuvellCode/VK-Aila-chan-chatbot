CREATE TABLE IF NOT EXISTS `AnonymBot.cf_messages` (
`id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=10489 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `AnonymBot.cf_responses` (
`id` int(11) NOT NULL,
  `response` varchar(20000) CHARACTER SET utf8 NOT NULL,
  `date` varchar(20) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1323 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `AnonymBot.cf_users` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(1000) CHARACTER SET utf8 NOT NULL,
  `user_sex` int(11) NOT NULL,
  `settings_waiting` int(11) NOT NULL,
  `user_sex_choose` int(11) NOT NULL,
  `talking` int(11) NOT NULL,
  `sent_messages` int(11) NOT NULL,
  `group_member` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=939 DEFAULT CHARSET=latin1;
