INSERT INTO `registration_cycles` (`id`, `cycleName`, `cycleStatus`, `created_at`,`updated_at`) VALUES
(1, 'cycle_1_2025', 'inactive', '2025-01-01','2025-01-01'),
(2, 'cycle_2_2025', 'active', '2025-06-01','2025-03-01');

INSERT INTO `youth_users` (`id`, `user_id`, `batchNo`, `duplicationScan`, `youth_personal_id`, `youthType`, `skills`, `created_at`, `updated_at`) VALUES
(1, 1, 1388163, NULL, '8627716', 'OSY', 'drawing', '2025-12-16 01:04:53', '2025-12-16 01:04:53'),
(2, 1, 5554306, NULL, '7077082', 'ISY', 'farming', '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(3, 1, 5443644, NULL, '8830669', 'ISY', 'gardening', '2025-12-16 01:25:02', '2025-12-16 01:25:02'),
(4, 1, 3177826, NULL, '1339009', 'ISY', 'Boxing', '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(5, 1, 6384105, NULL, '5651783', 'ISY', 'decorating', '2025-12-16 01:41:06', '2025-12-16 01:41:06'),
(6, 1, 5348390, NULL, '4053252', 'OSY', 'farming', '2025-12-16 01:44:08', '2025-12-16 01:44:08'),
(7, 1, 4941306, NULL, '7133291', 'OSY', 'arm wrestling', '2025-12-16 01:48:58', '2025-12-16 01:48:58'),
(8, 1, 3773651, NULL, '5091722', 'OSY', 'drawing', '2025-12-16 01:51:11', '2025-12-16 01:51:11');

INSERT INTO `youth_infos` (`id`, `youth_user_id`, `fname`, `mname`, `lname`, `address`, `dateOfBirth`, `placeOfBirth`, `contactNo`, `height`, `weight`, `religion`, `occupation`, `sex`, `civilStatus`, `gender`, `noOfChildren`, `created_at`, `updated_at`) VALUES
(1, 1, 'Fareed', 'cristobal', 'Saavedra', 'Zone 3', '2008-07-16', 'salaan', '09101245441', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:04:53', '2025-12-16 01:04:53'),
(2, 2, 'Rodjan', 'N/A', 'Del rosaryo', 'Zone 2', '2005-06-21', 'Salaan', '09108904619', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(3, 3, 'Bryan kim', 'N/A', 'Faustino', 'Sittio Lugakit', '2004-06-16', 'Talon-Talon', '09550163241', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:25:03', '2025-12-16 01:25:03'),
(4, 4, 'kenneth', 'N/A', 'Enriquez', 'Sittio San Antonio', '2005-07-05', 'Salaan', '09676514561', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(5, 5, 'Kristine', 'Saavedra', 'Lim', 'Zone 4', '2007-06-04', 'salaan', '09550163241', NULL, NULL, 'Christianity', NULL, 'Female', 'Single', NULL, NULL, '2025-12-16 01:41:06', '2025-12-16 01:41:06'),
(6, 6, 'severo III', 'N/A', 'francisco', 'Sittio Carreon', '2006-05-16', 'salaan', '09108654451', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:44:08', '2025-12-16 01:44:08'),
(7, 7, 'ferdinan', 'ocampo', 'francisco', 'Sittio Hapa', '2006-02-16', 'putik', '09654585578', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:48:58', '2025-12-16 01:48:58'),
(8, 8, 'lady kaye', 'N/A', 'Alviar', 'Sittio Balunu', '2005-01-19', 'bolong', '09854547770', NULL, NULL, 'Christianity', NULL, 'Male', 'Single', NULL, NULL, '2025-12-16 01:51:11', '2025-12-16 01:51:11');

INSERT INTO `educ_b_g_s` (`id`, `youth_user_id`, `level`, `nameOfSchool`, `periodOfAttendance`, `yearGraduate`, `created_at`, `updated_at`) VALUES
(1, 2, 'Elementary', 'Salaan Elementary school', '2014-06-17', '2014', '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(2, 2, 'HighSchool', 'Salaan high school', '2022-06-14', '2022', '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(3, 2, 'College', 'ZPPSU', '2025-12-16', NULL, '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(4, 3, 'Elementary', 'Talon-Tolong elementary school', '2016-06-10', '2016', '2025-12-16 01:25:03', '2025-12-16 01:25:03'),
(5, 3, 'HighSchool', 'Manicahan national highschool', '2022-03-16', '2022', '2025-12-16 01:25:03', '2025-12-16 01:25:03'),
(6, 3, 'College', 'ZCMST', '2025-12-16', NULL, '2025-12-16 01:25:03', '2025-12-16 01:25:03'),
(7, 4, 'Elementary', 'Ayala Central School', '2017-06-19', '2017', '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(8, 4, 'HighSchool', 'Ayala National High School', '2023-06-14', '2023', '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(9, 4, 'College', 'Western Mindanao State University', '2025-12-16', NULL, '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(10, 5, 'Elementary', 'Ayala Central School', '2019-06-16', '2019', '2025-12-16 01:41:06', '2025-12-16 01:41:06'),
(11, 5, 'HighSchool', 'Zamboanga Chong Hua High School', '2025-12-16', NULL, '2025-12-16 01:41:06', '2025-12-16 01:41:06');

INSERT INTO `validated_youths` (`id`, `youth_user_id`, `registration_cycle_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-12-16 01:04:53', '2025-12-16 01:04:53'),
(2, 2, 1, '2025-12-16 01:14:19', '2025-12-16 01:14:19'),
(3, 3, 1, '2025-12-16 01:25:03', '2025-12-16 01:25:03'),
(4, 4, 1, '2025-12-16 01:37:03', '2025-12-16 01:37:03'),
(5, 5, 1, '2025-12-16 01:41:06', '2025-12-16 01:41:06'),
(6, 6, 1, '2025-12-16 01:44:08', '2025-12-16 01:44:08'),
(7, 7, 1, '2025-12-16 01:48:58', '2025-12-16 01:48:58'),
(8, 8, 1, '2025-12-16 01:51:11', '2025-12-16 01:51:11');
