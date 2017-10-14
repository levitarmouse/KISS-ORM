-- with root user

CREATE DATABASE IF NOT EXISTS `kissf_db`
        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

USE `kissf_db`;

CREATE TABLE `kissf_users` (
  `id` int(11) NOT NULL,
  `user` varchar(30) NOT NULL,
  `email` varchar(70) NOT NULL,
  `passwork` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `kissf_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user` (`user`);

ALTER TABLE `kissf_users`
     MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE USER 'kissf_user'@'%' IDENTIFIED by 'kissf_pass';

GRANT SELECT, INSERT, UPDATE, DELETE ON `kissf\_db`.* TO 'kissf_user'@'%';

INSERT INTO `kissf_users`(`id`,`user`,`email`,`passwork`) VALUES ( '1', 'kissf', 'kissf@levitarmouse.com', sha1('kissf#5' ));