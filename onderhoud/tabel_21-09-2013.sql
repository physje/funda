-- phpMyAdmin SQL Dump
-- version 4.0.5
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 21 sep 2013 om 14:05
-- Serverversie: 5.5.29
-- PHP-versie: 5.3.17

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_abonnement`
--

CREATE TABLE IF NOT EXISTS `funda_abonnement` (
  `zoek_id` int(3) NOT NULL,
  `member_id` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_huizen`
--

CREATE TABLE IF NOT EXISTS `funda_huizen` (
  `funda_id` int(8) NOT NULL,
  `url` text NOT NULL,
  `adres` text NOT NULL,
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
  `verkocht` set('0','1','2') NOT NULL DEFAULT '0',
  `offline` set('0','1') NOT NULL DEFAULT '0',
  `open_huis` set('0','1') NOT NULL DEFAULT '0',
  UNIQUE KEY `funda_id` (`funda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_kalender`
--

CREATE TABLE IF NOT EXISTS `funda_kalender` (
  `huis` int(11) NOT NULL,
  `start` int(11) NOT NULL,
  `einde` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_kenmerken`
--

CREATE TABLE IF NOT EXISTS `funda_kenmerken` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `omschrijving` text NOT NULL,
  `kenmerk` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_lists`
--

CREATE TABLE IF NOT EXISTS `funda_lists` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `user` int(3) NOT NULL,
  `active` set('0','1') NOT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_list_resultaat`
--

CREATE TABLE IF NOT EXISTS `funda_list_resultaat` (
  `list` int(2) NOT NULL,
  `huis` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_log`
--

CREATE TABLE IF NOT EXISTS `funda_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tijd` int(11) NOT NULL,
  `type` set('error','info','debug') NOT NULL,
  `opdracht` int(11) NOT NULL,
  `huis` int(11) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_members`
--

CREATE TABLE IF NOT EXISTS `funda_members` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `level` int(1) NOT NULL,
  `mail` text NOT NULL,
  `account` int(3) NOT NULL,
  `lastLogin` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

INSERT INTO `funda_members` (`id`, `name`, `username`, `password`, `level`, `mail`, `account`, `lastLogin`) VALUES
(1, 'Admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 3, '', 0, 0);

-- --------------------------------------------------------


--
-- Tabelstructuur voor tabel `funda_prijzen`
--

CREATE TABLE IF NOT EXISTS `funda_prijzen` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `prijs` int(6) NOT NULL DEFAULT '0',
  `tijd` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_resultaat`
--

CREATE TABLE IF NOT EXISTS `funda_resultaat` (
  `zoek_id` int(2) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL,
  `verkocht` set('0','1','2') NOT NULL DEFAULT '0',
  `open_huis` set('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_zoeken`
--

CREATE TABLE IF NOT EXISTS `funda_zoeken` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(3) NOT NULL,
  `active` set('0','1') NOT NULL,
  `naam` text NOT NULL,
  `url` text NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
