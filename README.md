Author: ThriftyNick
Version: 1.0.0

Summary
------------
Places a datacap for logged-in users on specified filetypes within a target directory on Apache webserver.  Used as a deterrent for password sharing on membership sites.

Installation
-------------
Note: It is assumed that your site runs on Apache webserver and that you have user authentication set up using Apache's built-in Authentication module. (Using either htaccess files or rules set in httpd.conf).

1. Select a schema for the traffics table to reside in or create one via your host's CPanel.  (suggested name: 'data_capper').  Also make sure there is a username and password set up for this schema.

2. If your site does not use SSL (https works as a prefix for your domain), you will need to enter your database credentials manually.  Open install.php in a text editor and near the top of the document inside the function called 'install()' change the 4 database variables in the else branch of the if/else statement to the login details for your database.  Look for the "TODO" comment above these 4 variables.

3. Change line 1 in datacapper/.htaccess to reflect the full path to the .htpasswd file on your site.

4. Change line 4 in datacapper/.htaccess to reflect the admin username for accessing the control panel.

5. FTP upload the folder 'datacapper' and its contents to the target directory (directory where you want to apply the datacap).

6. Navigate to the datacapper directory by typing the URL into your web browser.  It should prompt you to login.  Use the admin username that you specified in Step 4.

7. You should now be on the "Control Panel" page.

8. Click on "Installation" and fill out the form accordingly.

9. After you click "Install" it will generate authorizeDL.php, and append (or add to existing) .htaccess file to the target directory.  It will also create a 'traffics' table in the database schema that you specified.

10. Verify that these items now exist on your server.

11. Periodically purge the traffics table of old records via "Manage traffics table" in the control panel.


Algorithm
-------------
1. Logged-in user requests a file from the target directory.

2. .htaccess in the target directory intercepts the request and checks to ensure the referrer was authorizeDL.php (the controller script).

3. If the referrer is not authorizeDL.php, the user is redirected to it where the filesize of the requested file is logged in the traffics table.

4. If the user is not over the datacap within the specified timeframe, authorizeDL.php grants access to the requested file.

5. If the user is over the datacap, but the last logged traffic for the user is past the expiration period, the trafficsused is reset to 0 and
the user is granted access to the requested file.

*****Purge traffics table*******
1. Any records in traffics that are older than the expiration timeframe are deleted.  This needs to be performed periodically, since records will
remain in the table for visitors who don't regularly make requests to the target directory.
