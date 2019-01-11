1. Place zoiper-directory.xml in one of these locations depending on your install:
	a) /var/www/fusionpbx/resources/templates/provision/zoiper   (default)
	b) /etc/fusionpbx/resources/templates/provision/zoiper
	
2. Place the zoiper_dir folder in /var/www/fusionpbx/app

3. Change Owner to www-date: 
	chown -R www-data:www-data /var/www/fusion

4. Add these to Default Settings or a Domain
	Category: zoiper
	Subcategory: http_auth_user
	Type: text
	Value: myuser
	
	Category: zoiper
	Subcategory: http_auth_pass
	Type: text
	Value: mypassword

	Category: zoiper
	Subcategory: http_auth_type
	Type: text
	Value: basic
	
5. Add this to your /etc/nginx/sites-enabled/fusionpbx file under the 443 section:
	#Zoiper
    rewrite "^.*/provision/zoiper-directory.xml$" "/app/zoiper_dir/?file=zoiper-directory.xml";
	
6. Verify and reload
	nginx -t
	systemctl restart nginx
	
7. Provision the Zoiper Desktop App:
	Step 1: In the Zoiper App, Go to Zoiper Settings, Contacts, Add
	Step 2: Choose XML Directory
	Step 3: Enter the following Information:
		URL: https://example.net/app/contactprovision/zoiper-directory.xml
		Authentication Type: Basic HTTP
		User: myuser
		Pass: mypass
	Step 4: Exit out of Contacts and Save

You should now see all the users and extensions on the main Zoiper screen. 
