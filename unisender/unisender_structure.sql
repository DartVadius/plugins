/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  DartVadius
 * Created: Jun 6, 2017
 */

-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema test
-- -----------------------------------------------------


-- -----------------------------------------------------
-- Table `crm_unisender_voting_answer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_voting_answer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(255) NOT NULL,
  `project_id` VARCHAR(255) NOT NULL,
  `variant` VARCHAR(45) NULL,
  `hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  UNIQUE INDEX `index_var` (`user_id` ASC, `project_id` ASC))
ENGINE = MyISAM;

-- -----------------------------------------------------
-- Table `crm_unisender_delivery_contacts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_delivery_contacts` (
  `contact_id` INT(11) NOT NULL,
  `list_id` INT(11) NOT NULL,
  PRIMARY KEY (`list_id`, `contact_id`),
  INDEX `contact_id` (`contact_id` ASC))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `crm_unisender_delivery_list`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_delivery_list` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '0',
  `description` TEXT NULL DEFAULT NULL,
  `contact_id` INT(11) NULL DEFAULT NULL,
  `modify_datetime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8
COMMENT = 'contact list';


-- -----------------------------------------------------
-- Table `crm_unisender_project`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_project` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `contact_id` INT(11) NOT NULL,
  `template_type` VARCHAR(50) NULL DEFAULT NULL,
  `template_id` INT(11) NULL DEFAULT NULL,
  `recipients_list_type` VARCHAR(100) NULL DEFAULT NULL,
  `recipients_list_id` VARCHAR(255) NULL DEFAULT NULL,
  `status` INT(11) NOT NULL DEFAULT '0',
  `send_on_schedule` DATE NULL DEFAULT NULL,
  `schedule_time` TIME NULL DEFAULT NULL,
  `last_send` DATETIME NULL DEFAULT NULL,
  `type_send` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 47
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `crm_unisender_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_template` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `html` TEXT NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `crm_unisender_template_html`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_template_html` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '0',
  `description` TEXT NOT NULL,
  `contact_id` INT(11) NOT NULL DEFAULT '0',
  `public` ENUM('1','0') NOT NULL DEFAULT '0',
  `editable` ENUM('1','0') NOT NULL DEFAULT '0',
  `subject` VARCHAR(100) NOT NULL DEFAULT '0',
  `html` LONGTEXT NOT NULL,
  `modify_datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `crm_unisender_template_voting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_template_voting` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `contact_id` INT(11) NOT NULL,
  `public` ENUM('1','0') NOT NULL DEFAULT '0',
  `editable` ENUM('1','0') NOT NULL DEFAULT '0',
  `subject` VARCHAR(100) NOT NULL,
  `text` TEXT NOT NULL,
  `modify_datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `crm_unisender_template_voting_variants`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_unisender_template_voting_variants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `voting_id` INT(11) NOT NULL,
  `variant_number` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
