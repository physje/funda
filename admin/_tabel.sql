-- phpMyAdmin SQL Dump
-- version 3.4.11.1
-- http://www.phpmyadmin.net
--
-- Machine: mysql02.totaalholding.nl
-- Genereertijd: 16 nov 2012 om 19:43
-- Serverversie: 5.0.95
-- PHP-Versie: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databank: `draije1a_db_thijs`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `new_funda_huizen`
--

CREATE TABLE IF NOT EXISTS `new_funda_huizen` (
  `key` int(6) NOT NULL auto_increment,
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
  `start` int(10) NOT NULL default '0',
  `eind` int(10) NOT NULL default '0',
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `new_funda_kenmerken`
--

CREATE TABLE IF NOT EXISTS `new_funda_kenmerken` (
  `key` int(5) NOT NULL,
  `funda_id` int(8) NOT NULL,  
  `omschrijving` text NOT NULL,
  `kenmerk` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `new_funda_resultaat`
--

CREATE TABLE IF NOT EXISTS `new_funda_resultaat` (
  `zoek_id` int(2) NOT NULL,
  `funda_id` int(8) NOT NULL,
  `prijs` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
