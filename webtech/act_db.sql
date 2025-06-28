-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2024 at 01:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";




CREATE TABLE `logins` (
  `id` int(11) NOT NULL,
  `register_id` int(6) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `logins` (`id`, `register_id`, `username`, `email`, `password`) VALUES
(3, 5, 'jem123', 'jem@gmail.com', '$2y$10$SN8xzm/B.2dNFi8paCaM9.r2SgJVChdACgrCDB6ZS3ytqN1adBhqK');

-- --------------------------------------------------------



CREATE TABLE `registers` (
  `id` int(11) NOT NULL,
  `faculty_ID` int(6) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `registers` (`id`, `faculty_ID`, `fname`, `mname`, `lname`, `birthdate`, `age`) VALUES
(5, 6, 'Jemmuel', 'Balde', 'Rama', '2008-02-06', 16);

-- --------------------------------------------------------



CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` int(6) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_level` enum('1','2','3','4','5') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `students` (`id`, `student_id`, `fullname`, `course`, `year_level`) VALUES
(2, 0, 'JEMEUEL', 'BSIT', '2');



CREATE TABLE `subjects` (
    `subject_id` INT(11) NOT NULL AUTO_INCREMENT, -- Unique identifier for each subject
    `course_code` VARCHAR(20) NOT NULL, -- Foreign key referencing course_code in courses
    `subject_detail` VARCHAR(255) NOT NULL, -- Detailed description of the subject
    `units` INT(11) NOT NULL, -- Number of units for the subject
    `lab` INT(11) NOT NULL DEFAULT 0, -- Number of lab hours (default is 0)
    `lecture` INT(11) NOT NULL DEFAULT 0, -- Number of lecture hours (default is 0)
    `pre_requisite` VARCHAR(255) DEFAULT NULL, -- Pre-requisite subjects, if any
    `year_level` ENUM('1', '2', '3', '4', '5') NOT NULL, -- Year level required for the subject
    `semester` ENUM('1', '2') NOT NULL, -- Semester (1 for First Semester, 2 for Second Semester)
    PRIMARY KEY (`subject_id`), -- Primary key on subject_id
    CONSTRAINT `fk_subject_course_code`
      FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`)
      ON DELETE CASCADE ON UPDATE CASCADE -- Ensures foreign key relationship
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE courses (
    course_id INT(11) NOT NULL AUTO_INCREMENT, -- Unique identifier for each course
    program VARCHAR(100) NOT NULL, -- Name of the program
    PRIMARY KEY (course_id) -- Set course_id as the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



ALTER TABLE `logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `register_id` (`register_id`);

dexes for table `registers`
--
ALTER TABLE `registers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_ID` (`faculty_ID`);


ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);


ALTER TABLE `logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


ALTER TABLE `registers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;


ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;



ALTER TABLE `logins`
  ADD CONSTRAINT `logins_ibfk_1` FOREIGN KEY (`register_id`) REFERENCES `registers` (`id`);
COMMIT;

ALTER TABLE `students`
  MODIFY `course` INT(11) NOT NULL;


ALTER TABLE `students`
  ADD CONSTRAINT `fk_course`
  FOREIGN KEY (`course`) REFERENCES `courses` (`course_id`)
  ON DELETE CASCADE ON UPDATE CASCADE; 


ALTER TABLE `students`
  MODIFY `course` INT(11) NOT NULL;


ALTER TABLE `students`
  ADD CONSTRAINT `fk_course`
  FOREIGN KEY (`course`) REFERENCES `courses` (`course_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE courses
ADD course_code VARCHAR(20) NOT NULL UNIQUE; -- Adds the course_code column

ALTER TABLE `registers`
ADD COLUMN `profile_image` LONGBLOB;

ALTER TABLE subjects MODIFY pre_requisite VARCHAR(255);

ALTER TABLE logins
ADD COLUMN login_attempts INT DEFAULT 0,
ADD COLUMN last_attempt DATETIME DEFAULT NULL;
