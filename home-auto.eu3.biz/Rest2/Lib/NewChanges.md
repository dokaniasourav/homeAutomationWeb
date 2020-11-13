##New Database -- *greenovation_db*

The following are the changes in the table names:

* devices             -- devices_tbl
* modules             -- modules_tbl
* modules_identity    -- modules_mac_tbl  
* session             -- sessions_tbl
* users               -- users_tbl
* users_modules       -- authorisation_tbl
* user_detail         -- users_info_tbl

### Changing some table names:

	#	Name	    Type	             
	1	module_id   PrimaryIndex int(10) 
	2	device_id   Primary	tinyint(3)
	3	name	    varchar(100)  --> appellation
	4	type	    tinyint(3)    --> device_type
	5	state	    tinyint(3)    --> device_state
	6	enabled	    tinyint(4)
	7	detail	    varchar(255)  --> description
	

            Name                    Type
 	1	module_id               Primary	int(10)
 	2	name	                varchar(100)_ci
 	3	num_of_device           tinyint(3)
 	4	created	                timestamp_timestamp()
 	5	detail	                varchar(255)_ci
 	6	configuration           varchar(2000)_ci
 	7	pin_map             	varchar(255)_ci	
 	8	credential              varchar(4000)_ci	
 	9	firmware    	        varchar(100)_ci	
 	10	last_ota    	        timestamp	