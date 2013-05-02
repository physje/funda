-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 27 Apr 2013 om 20:38
-- Serverversie: 5.1.41
-- PHP-Versie: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Tabelstructuur voor tabel `funda_huizen`
--
-- Gecreëerd: 26 Apr 2013 om 09:42
-- Laatst bijgewerkt: 27 Apr 2013 om 11:50
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
  `latitude` float(10,6) NOT NULL,
  `longitude` float(10,6) NOT NULL,
  `start` int(10) NOT NULL DEFAULT '0',
  `eind` int(10) NOT NULL DEFAULT '0',
  `verkocht` set('0','1') NOT NULL DEFAULT '0',
  `offline` set('0','1') NOT NULL DEFAULT '0',
  UNIQUE KEY `funda_id` (`funda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_kenmerken`
--
-- Gecreëerd: 26 Apr 2013 om 09:35
-- Laatst bijgewerkt: 26 Apr 2013 om 09:36
--

CREATE TABLE IF NOT EXISTS `funda_kenmerken` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `omschrijving` text NOT NULL,
  `kenmerk` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_lists`
--
-- Gecreëerd: 24 Apr 2013 om 15:16
-- Laatst bijgewerkt: 26 Apr 2013 om 17:20
--

CREATE TABLE IF NOT EXISTS `funda_lists` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `active` set('0','1') NOT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_list_resultaat`
--
-- Gecreëerd: 24 Apr 2013 om 15:16
-- Laatst bijgewerkt: 26 Apr 2013 om 17:15
--

CREATE TABLE IF NOT EXISTS `funda_list_resultaat` (
  `list` int(3) NOT NULL,
  `huis` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_log`
--
-- Gecreëerd: 02 Apr 2013 om 12:10
-- Laatst bijgewerkt: 27 Apr 2013 om 20:33
--

CREATE TABLE IF NOT EXISTS `funda_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tijd` int(11) NOT NULL,
  `type` set('error','info','debug') NOT NULL,
  `opdracht` int(3) NOT NULL,
  `huis` int(8) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_prijzen`
--
-- Gecreëerd: 26 Apr 2013 om 09:36
-- Laatst bijgewerkt: 26 Apr 2013 om 22:07
--

CREATE TABLE IF NOT EXISTS `funda_prijzen` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL DEFAULT '0',
  `tijd` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_resultaat`
--
-- Gecreëerd: 27 Apr 2013 om 13:31
-- Laatst bijgewerkt: 27 Apr 2013 om 13:50
--

CREATE TABLE IF NOT EXISTS `funda_resultaat` (
  `zoek_id` int(3) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL,
  `verkocht` set('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Tabelstructuur voor tabel `funda_zoeken`
--
-- Gecreëerd: 24 Apr 2013 om 15:16
-- Laatst bijgewerkt: 27 Apr 2013 om 11:50
--

CREATE TABLE IF NOT EXISTS `funda_zoeken` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `active` set('0','1') NOT NULL,
  `mail` set('0','1') NOT NULL DEFAULT '1',
  `adres` text NOT NULL,
  `naam` text NOT NULL,
  `url` text NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;