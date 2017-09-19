--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010-2016
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--  Konrad <konrd@yahoo.com>
--
--	add this in Inbound Routes before transfer to use it:
--	action set origination_callee_id_name=${luarun cidlookup.lua ${uuid}}

--define the trim function
	require "resources.functions.trim"

--define the explode function
	require "resources.functions.explode"

--create the api object
	api = freeswitch.API();

--Get the arguments
	extension = argv[1];
	domain_name = argv[2];

--include config.lua
	require "resources.functions.config";

--set the debug options
	debug["sql"] = false;
	
--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--debug
	--freeswitch.consoleLog("NOTICE", "[cf_loop_detect] Initial extension: "..extension.."\n");
	--freeswitch.consoleLog("NOTICE", "[cf_loop_detect] Domain Name: "..domain_name.."\n");
	
-- ensure that we have a fresh status on exit
	session:setVariable("cf_loop", "")

function get_cf (extension, domain_name)
	--connect to the database
		api = freeswitch.API();
		local Database = require "resources.functions.database";
		dbh = Database.new('system');
		sql = "SELECT v_extensions.forward_all_destination, v_extensions.forward_all_enabled FROM v_extensions  ";
		sql = sql .. "INNER JOIN v_domains ON v_domains.domain_uuid = v_extensions.domain_uuid   ";	
		sql = sql .. "WHERE v_extensions.extension = :extension  and  v_domains.domain_name = :domain_name  "
		sql = sql .. "limit 1 "
		local params = {extension = extension, domain_name = domain_name}	
		
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[cidlookup] SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
		end	
		

		
		dbh:query(sql, params, function(row)
			if (row.forward_all_enabled == 'true') then
				forward_all_destination = row.forward_all_destination;
				forward_all_enabled = row.forward_all_enabled;
			else
				forward_all_destination = "empty";
			end
		end);
	
		return forward_all_enabled, forward_all_destination;
end	
	
--Get the initial cfa
	flag = 'false';
	extension_array = {};
	extension_array[0] = {};
	extension_array[0]["extension"] = extension;
	extension_array[0]["forward_all_enabled"], extension_array[0]["forward_all_destination"] = get_cf(extension, domain_name)
	
	if (extension_array[0]["forward_all_enabled"] == "true") then
		freeswitch.consoleLog("NOTICE", "[cf_loop_detect] forward all is enabled: "..extension_array[0]["extension"].." forwards to "..extension_array[0]["forward_all_destination"].."\n");
		--create array for next leg and get information
			extension_array[1] = {};
			extension_array[1]["extension"] = extension_array[0]["forward_all_destination"];
			extension_array[1]["forward_all_enabled"], extension_array[1]["forward_all_destination"] = get_cf(extension_array[0]["forward_all_destination"], domain_name)
			if (extension_array[1]["forward_all_enabled"] == "true") then
				freeswitch.consoleLog("NOTICE", "[cf_loop_detect] forward all is enabled: "..extension_array[1]["extension"].." forwards to "..extension_array[1]["forward_all_destination"].."\n");
				--loop present
					if (extension_array[1]["forward_all_destination"] == extension_array[0]["extension"]) then
						flag = "true";
						freeswitch.consoleLog("NOTICE", "[cf_loop_detect] Loop Detected!!\n");
					end
				--not a loop, get the next leg
					extension_array[2] = {};
					extension_array[2]["extension"] = extension_array[1]["forward_all_destination"];
					extension_array[2]["forward_all_enabled"], extension_array[2]["forward_all_destination"] = get_cf(extension_array[1]["forward_all_destination"], domain_name)
					if (extension_array[2]["forward_all_enabled"] == "true") then
						freeswitch.consoleLog("NOTICE", "[cf_loop_detect] forward all is enabled: "..extension_array[2]["extension"].." forwards to "..extension_array[2]["forward_all_destination"].."\n");
					--loop present
						if (extension_array[2]["forward_all_destination"] == extension_array[0]["extension"] or extension_array[2]["forward_all_destination"] == extension_array[1]["extension"]) then

							flag = "true";
							freeswitch.consoleLog("NOTICE", "[cf_loop_detect] Loop Detected!!\n");
						end
					end
			end
	end

if (flag == "true") then
	session:hangup("CALL_REJECTED")
end
