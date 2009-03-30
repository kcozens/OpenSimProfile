-- phpMyAdmin SQL Dump
-- version 2.7.0-beta1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generatie Tijd: 30 Mar 2009 om 22:05
-- Server versie: 5.0.75
-- PHP Versie: 5.2.6-3ubuntu2
-- 
-- Database: `osprofile`
-- 

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `usernotes`
-- 

CREATE TABLE `usernotes` (
  `useruuid` varchar(36) NOT NULL,
  `targetuuid` varchar(36) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY  (`useruuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `userpicks`
-- 

CREATE TABLE `userpicks` (
  `pickuuid` varchar(36) NOT NULL,
  `creatoruuid` varchar(36) NOT NULL,
  `toppick` enum('true','false') NOT NULL,
  `parceluuid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `desc` text NOT NULL,
  `snapshotuuid` varchar(36) NOT NULL,
  `user` varchar(255) NOT NULL,
  `originalname` varchar(255) NOT NULL,
  `simname` varchar(255) NOT NULL,
  `posglobal` varchar(255) NOT NULL,
  `sortorder` int(2) NOT NULL,
  `enabled` enum('true','false') NOT NULL,
  PRIMARY KEY  (`pickuuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Tabel structuur voor tabel `userprofile`
-- 

CREATE TABLE `userprofile` (
  `useruuid` varchar(36) NOT NULL,
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
  PRIMARY KEY  (`useruuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
