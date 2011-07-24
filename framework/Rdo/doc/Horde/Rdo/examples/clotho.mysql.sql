CREATE TABLE `clotho_calendars` (
  `calendar_id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_name` varchar(128) NOT NULL,
  `calendar_hoursinday` int(11) NOT NULL,
  `calendar_hoursinweek` int(11) NOT NULL,
  `calendar_type` varchar(32) NOT NULL,
  `calendar_data` text NOT NULL,
  PRIMARY KEY (`calendar_id`)
);

CREATE TABLE `clotho_resource_availability` (
  `availability_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `availability_date` int(11) NOT NULL,
  `availability_hours` decimal(10,0) NOT NULL,
  PRIMARY KEY (`availability_id`)
);

CREATE TABLE `clotho_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type` char(1) NOT NULL,
  `resource_name` varchar(128) NOT NULL,
  `resource_uid` varchar(64) DEFAULT NULL,
  `resource_base_calendar` int(11) NOT NULL,
  `resource_start` int(11) DEFAULT NULL,
  `resource_finish` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
);

CREATE TABLE `clotho_wbs_dependencies` (
  `dependency_id` int(11) NOT NULL AUTO_INCREMENT,
  `dependency_type` char(1) NOT NULL,
  `dependency_lhs_item` int(11) NOT NULL,
  `dependency_rhs_item` int(11) NOT NULL,
  `dependency_duration` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`dependency_id`)
);

CREATE TABLE `clotho_wbs_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(128) DEFAULT NULL,
  `item_parent` int(11) NOT NULL,
  `item_duration` varchar(20) DEFAULT NULL,
  `item_start` int(11) DEFAULT NULL,
  `item_start_fixed` int(11) NOT NULL,
  `item_finish` int(11) DEFAULT NULL,
  `item_finish_fixed` int(11) NOT NULL,
  PRIMARY KEY (`item_id`)
);

CREATE TABLE `clotho_wbs_resources` (
  `item_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL
);
