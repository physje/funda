-- phpMyAdmin SQL Dump
-- version 3.5.5
-- http://www.phpmyadmin.net
--
-- Genereertijd: 31 mrt 2013 om 14:23
-- Serverversie: 5.5.29
-- PHP-versie: 5.3.17

--
-- Tabelstructuur voor tabel `funda_huizen`
--

CREATE TABLE IF NOT  EXISTS `new_funda_huizen` (
  `funda_id` int(8) NOT NULL,
  `url` text NOT NULL,
  `adres` text NOT NULL,
  `PC_cijfers` text NOT NULL,
  `PC_letters` varchar(2) NOT NULL,
  `plaats` text NOT NULL,
  `wijk` text NOT NULL,
  `thumb` text NOT NULL,
  `N_deg` text NOT NULL,
  `N_dec` text NOT NULL,
  `O_deg` text NOT NULL,
  `O_dec` text NOT NULL,
  `start` int(10) NOT NULL DEFAULT '0',
  `eind` int(10) NOT NULL DEFAULT '0',
  `verkocht` set('0','1') NOT NULL DEFAULT '0',
  `offline` set('0','1') NOT NULL DEFAULT '0',
  UNIQUE KEY `funda_id` (`funda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_kenmerken`
--

CREATE TABLE IF NOT  EXISTS `new_funda_kenmerken` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `omschrijving` text NOT NULL,
  `kenmerk` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=98098 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_log`
--

CREATE TABLE IF NOT  EXISTS `new_funda_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tijd` int(11) NOT NULL,
  `type` set('error','info','debug') NOT NULL,
  `opdracht` int(11) NOT NULL,
  `huis` int(11) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=822178 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_prijzen`
--

CREATE TABLE IF NOT  EXISTS `new_funda_prijzen` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `prijs` int(6) NOT NULL DEFAULT '0',
  `tijd` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=271682 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_resultaat`
--

CREATE TABLE IF NOT  EXISTS `new_funda_resultaat` (
  `zoek_id` int(2) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `funda_zoeken`
--

CREATE TABLE IF NOT  EXISTS `new_funda_zoeken` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` set('0','1') NOT NULL,
  `mail` set('0','1') NOT NULL DEFAULT '1',
  `adres` text NOT NULL,
  `naam` text NOT NULL,
  `url` text NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;
