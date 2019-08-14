CREATE TABLE `funda_abonnement` (
  `zoek_id` int(3) NOT NULL,
  `member_id` int(3) NOT NULL,
  `soort` set('mail','push') NOT NULL DEFAULT 'mail'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_gemeentes` (
  `PC` int(4) NOT NULL,
  `plaats` text NOT NULL,
  `gemeente` text NOT NULL,
  `provincie` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `funda_huizen` (
  `funda_id` int(8) NOT NULL,
  `url` text NOT NULL,
  `adres` text NOT NULL,
  `straat` text NOT NULL,
  `nummer` int(4) NOT NULL,
  `letter` text NOT NULL,
  `toevoeg` int(4) NOT NULL,
  `PC_cijfers` text NOT NULL,
  `PC_letters` varchar(2) NOT NULL,
  `plaats` text NOT NULL,
  `wijk` text NOT NULL,
  `thumb` text NOT NULL,
  `makelaar` text NOT NULL,
  `latitude` float(10,6) NOT NULL,
  `longitude` float(10,6) NOT NULL,
  `start` int(10) NOT NULL DEFAULT '0',
  `eind` int(10) NOT NULL DEFAULT '0',
  `afgemeld` int(10) NOT NULL DEFAULT '0',
  `verkocht` set('0','1','2') NOT NULL DEFAULT '0',
  `offline` set('0','1') NOT NULL DEFAULT '0',
  `open_huis` set('0','1') NOT NULL DEFAULT '0',
  `details` set('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_ignore` (
  `id` int(8) NOT NULL,
  `funda_id` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_kalender` (
  `huis` int(11) NOT NULL,
  `start` int(11) NOT NULL,
  `einde` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_kenmerken` (
  `id` int(6) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `omschrijving` text NOT NULL,
  `kenmerk` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `funda_lists` (
  `id` int(2) NOT NULL,
  `user` int(3) NOT NULL,
  `active` set('0','1') NOT NULL,
  `name` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_list_resultaat` (
  `list` int(2) NOT NULL,
  `huis` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_log` (
  `id` int(11) NOT NULL,
  `tijd` int(11) NOT NULL,
  `type` set('error','info','debug') NOT NULL,
  `opdracht` int(11) NOT NULL,
  `huis` int(11) NOT NULL,
  `message` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_members` (
  `id` int(3) NOT NULL,
  `name` text NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `level` int(1) NOT NULL,
  `mail` text NOT NULL,
  `userkey` text NOT NULL,
  `api_token` text NOT NULL,
  `account` int(3) NOT NULL,
  `lastLogin` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

INSERT INTO `funda_members` (`id`, `name`, `username`, `password`, `level`, `mail`, `account`, `lastLogin`) VALUES
(1, 'Admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 3, '', 0, 0);

CREATE TABLE `funda_PBK` (
  `start` int(11) NOT NULL,
  `eind` int(11) NOT NULL,
  `regio` text NOT NULL,
  `waarde` decimal(4,1) NOT NULL,
  `comment` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_prijzen` (
  `id` int(5) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(6) NOT NULL DEFAULT '0',
  `tijd` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_resultaat` (
  `id` int(11) NOT NULL,
  `zoek_id` int(2) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL,
  `verkocht` set('0','1','2') NOT NULL DEFAULT '0',
  `open_huis` set('0','1') NOT NULL DEFAULT '0',
  ` nieuw` set('0','1') NOT NULL DEFAULT '1',
  `mail_prijs` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_straten` (
  `id` int(6) NOT NULL,
  `active` set('0','1') NOT NULL DEFAULT '1',
  `naam_leesbaar` text NOT NULL,
  `naam_funda` text NOT NULL,
  `stad` text NOT NULL,
  `last_checked` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `funda_verdeling` (
  `uur` int(2) NOT NULL,
  `opdracht` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `funda_zoeken` (
  `id` int(11) NOT NULL,
  `user` int(3) NOT NULL,
  `naam` text NOT NULL,
  `url` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `funda_huizen`
  ADD UNIQUE KEY `funda_id` (`funda_id`);

ALTER TABLE `funda_ignore`
  ADD KEY `id` (`id`);

ALTER TABLE `funda_kenmerken`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `funda_lists`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `funda_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `funda_members`
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `funda_prijzen`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `funda_resultaat`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `funda_straten`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `funda_zoeken`
  ADD UNIQUE KEY `id` (`id`);


ALTER TABLE `funda_ignore`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_kenmerken`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_lists`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_members`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_prijzen`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_resultaat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_straten`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT;

ALTER TABLE `funda_zoeken`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
