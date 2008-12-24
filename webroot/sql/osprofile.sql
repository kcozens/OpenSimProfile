-- phpMyAdmin SQL Dump
-- version 2.7.0-beta1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generatie Tijd: 24 Dec 2008 om 22:40
-- Server versie: 5.0.67
-- PHP Versie: 5.2.6-2ubuntu5
-- 
-- Database: `osprofile`
-- 

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `userclassifieds`
-- 

CREATE TABLE `userclassifieds` (
  `userUUID` varchar(36) NOT NULL,
  `ClassifiedID` varchar(36) NOT NULL,
  `Category` int(2) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Desc` text NOT NULL,
  `ParcelID` varchar(36) NOT NULL,
  `ParentEstate` int(2) NOT NULL,
  `SnapshotID` varchar(36) NOT NULL,
  `PosGlobal` varchar(255) NOT NULL,
  `ClassifiedFlags` int(2) NOT NULL,
  `PriceForListing` int(6) NOT NULL,
  PRIMARY KEY  (`userUUID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `usernotes`
-- 

CREATE TABLE `usernotes` (
  `userUUID` varchar(36) NOT NULL,
  `TargetID` varchar(36) NOT NULL,
  `Notes` text NOT NULL,
  PRIMARY KEY  (`userUUID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `userpicks`
-- 

CREATE TABLE `userpicks` (
  `userUUID` varchar(36) NOT NULL,
  `PickID` varchar(36) NOT NULL,
  `CreatorID` varchar(36) NOT NULL,
  `TopPick` enum('0','1') NOT NULL,
  `ParcelID` varchar(36) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `SnapshotID` varchar(36) NOT NULL,
  `PosGlobal` varchar(255) NOT NULL,
  `SortOrder` int(2) NOT NULL,
  `Enabled` binary(1) NOT NULL,
  PRIMARY KEY  (`userUUID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `userprofile`
-- 

CREATE TABLE `userprofile` (
  `userUUID` varchar(36) NOT NULL,
  `profilePartner` varchar(36) NOT NULL,
  `profileImage` varchar(36) NOT NULL,
  `profileAboutText` text NOT NULL,
  `profileAllowPublish` binary(1) NOT NULL,
  `profileMaturePublish` binary(1) NOT NULL,
  `profileURL` varchar(255) NOT NULL,
  `profileWantToMask` int(3) NOT NULL,
  `profileWantToText` text NOT NULL,
  `profileSkillsMask` int(3) NOT NULL,
  `profileSkillsText` text NOT NULL,
  `profileLanguages` text NOT NULL,
  `profileFirstImage` varchar(36) NOT NULL,
  `profileFirstText` text NOT NULL,
  PRIMARY KEY  (`userUUID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
