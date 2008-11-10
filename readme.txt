Welcome in the world of YACS!

About Yet Another Community System (YACS)
=========================================

YACS is a powerful set of PHP scripts that allow you to maintain a dynamic web
server either on the Internet, within your company, or at home.

YACS is intended for people that want full control and 'tweakability' over their
sites. If you're just looking for a very simple way to put up a site, or don't
know any HTML, you might want to use a 'simpler' service such as Blogger.
http://www.blogger.com/

A short summary of most important features is given below:
- Runs on your own server, or on a shared web site
- Post articles with web forms, by e-mail, or remotely (w:bloggar)
- Embed images and photos in articles --automatic resize
- Each section can be a weblog, a discussion board, a book of cooking recipes,
etc, or even a plain list of articles
- Overlay interface for PHP developers, to add extra functionality to articles,
such as polls or cooking recipes
- Display the content tree in Freemind
- Comments, with quoting
- Archives per week and per month
- The home page is updated automatically on article publishing
- Categories, sub-categories, etc. --Build your own Yahoo! or DMOZ...
- Real-time meetings with community members
- Private discussions and messages
- Search on any word --text of articles is fully indexed
- Multiple authors --actually, a community of contributors
- Articles are visible only on publication after review by editors
- Articles and sections can have dead-line to limit visibility over time
- A straightforward control panel, and a set of configuration panels
- File upload to articles , sections or categories
- Attach links to articles, sections or categories
- A comprehensive set of UBB-like codes are available to beautify your posts
- Integrated support of TinyMCE and of FCKEditor
- Fully customizable skins
- Easy integration of Google Maps
- Add a comprehensive web interface to existing collections of files
- Support audio-on demand and video-on demand
- Automatic web slideshow for shared photos
- RSS syndication
- Easy installation
- XML-RPC interface (implementing the Blogger API and metaWeblog API)
- ...

A more complete list of features is available at
http://www.yetanothercommunitysystem.com/go/features


Requirements for running YACS
=============================

If you don't know what we are talking about, ask your system administrator.

Web server:
	Yacs is used with the Apache web server most of the time.
	Following modules are recommanded:
	- mod_deflate for page compression over the wire
	- mod_rewrite for nice URLs, along rules set in .htaccess files
	- mod_expire for optimized caching of dynamic objects
	
Run-time:
   - PHP (version 4.3 or higher) with XML parser
   - The imap extension is necessary for mail integration (SMTP and POP3)
   - The LDAP PHP extension is required for LDAP user authentication
   - If the GD extension is available, YACS will resize uploaded images and build thumbnails
   - If the MING extension is available, you may benefit from dynamic Flash news
   - If the zip extension is activated, you will be able to upload .zip files
   - If the improved MySQL extension is available, YACS will use it
   - If the CURL extension is available, YACS will use it to parse external RSS feeds

Database:
   A database connection is needed for YACS to function correctly since most of
   its data is stored there.  This database must be created before you begin the
   install process. Sample instructions for this are provided below.
   Currently YACS only works with a MySQL database (version 3.23.38 or higher).

Permissions:
   YACS has to write to any PHP script (for example, during the update process),
   and to other places as well (for example, to write configuration files).
   It is assumed that the web daemon properly impersonates your FTP account, or
   that you have the ability to alter your directory permissions.
   Sample instructions are provided below to fix common permission issues.
   Also, you may be in trouble with servers running in safe mode.

Skills:
   You should have some knowledge of HTML and CSS to customize skins and
   templates. Also, we recommend you to practice blog, RSS, Wikis to further
   benefit from advanced features of YACS.


First installation of YACS
==========================

0. To get the most recent stable archive, visit

	http://www.yetanothercommunitysystem.com/go/download

1. Explode all files of the archive to a directory of your computer.
   Make sure that path names are preserved during the operation.

2. If your computer is not the target server, you will have to upload all files,
   using your favorite FTP program. To connect a Windows workstation to a
   secured Unix server visit http://winscp.net/ to get WinSCP.
   Make sure to preserve the directory structure of YACS.

3. (optional) If you are running Apache, you may upload the provided .htaccess
   file to your server, or a derivated version, to further secure access to your
   server, to implement nice error pages, etc.

4. YACS requires adequate rights to write files in this structure. An adequate
   setting for the setup is to chmod everything to 707.

	cd your_installation_directory
	chmod -R 707 .
	ls -l

   In case of PHP safe mode, you will have to chown every directory and file to
   the UID used by the web server to run YACS.

5. Launch the setup script through your web browser. It is named setup.php, and
   is located at the installation directory. Adapt yoursite and yourpath to your
   needs: http://www.yoursite.com/yourpath/yacs/setup.php.

   For example, if the root URL of your installation directory is

	http://www.foo.bar/~fancy

   then the setup script is at

	http://www.foo.bar/~fancy/yacs/setup.php

6. Pass through the several steps of the installation process. The setup script
   will prompt you for some information, and perform most of the installation
   on your behalf.

   During the setup you will have the opportunity to create a user account with
   associate privileges. Note carefully the password you have entered.

   During the process you will be provided with further instructions, if any.

7. (optional) After a successful installation you may attempt to improve on
   security and performance by setting particular file permissions, by tweaking
   .htaccess, and by changing YACS parameters.

8. Browse the YACS Control Panel to access configuration panels, to switch to
   any module, and to trigger the Content Assistant.

   You are now ready to upload and to share information with other members of
   your community! Thank you for your interest in YACS.



Never reinstall your server again!
==================================

YACS implements very powerful mechanisms to benefit from software upgrades.

All you have to do is to configure a reference source of code for your server,
and to check periodically for updated and new scripts.

To get more information on this feature visit

	http://www.yetanothercommunitysystem.com/go/upgrades

The default reference server is www.yetanothercommunitysystem.com, but you can
change this to use another server if you wish. IT managers will appreciate.

The update process is not automatic. Once a month, or to benefit unattended from
a new YACS feature exposed at http//www.yetanothercommunitysystem.com, go to the
Configuration Panel, select the System tab, then the Server software link.


Preparing a local MySQL server for YACS
=======================================

This assumes that you have a working MySQL server and client setup.

Installing MySQL is beyond the scope of this document.
For more information on MySQL go to http://www.mysql.org/

1. If you do not have a suitable database already, create one using
   the root or other privileged account (i.e., 'privileged_user' and
   'privileged_password' below) you set up when MySQL was installed.

	mysqladmin -uprivileged_user -pprivileged_password create yacs

2. If necessary create a user for that database which has the rights
   to create, alter, drop, select, insert, update, delete (again using
   the root administration account).

	mysql -uprivileged_user -pprivileged_password yacs

   A MySQL grant statement for a local access of this user (i.e., 'user' and
   'password' below) would look like this:

	grant all privileges on yacs.* to user@localhost identified by 'password';

3. Tables are created and populated during setup steps, providing you
   give adequate account information to YACS. Do not look for a single
   text file of MySQL statements, because there is not such a thing in
   the YACS archive. Actually, statements are dynamically prepared from
   within PHP scripts. Look at control/setup.php for more information
   on this topic.


Common permissions settings
===========================
As you may know, every directory and file on a Unix system has an owner, and
also an associated group. It also has a set of permission flags which specify
separate read, write and execute permissions for the 'user' (owner), 'group',
and 'other' (everyone else with an account on the computer). The 'ls' command
shows permissions and group associated with files when used with the -l option.

To understand permissions set by the 'chmod' command, remind that these are
numerically equivalent to those reported by the 'ls' command, namely:
- 7 means "may read, write, and execute"
- 6 means "may read and write"
- 4 means "may read"
- 0 means "forbidden access"

Normally, user/group information is set based on the FTP account used to upload
YACS files to the web server, for example foobar/users.

Ideally, a properly configured web server should impersonate you when executing
your scripts, for example with user/group information set to foobar/nobody.
In this case the server has exactly the same rights that you have through FTP,
and you can 'chmod' everything to 700 if you like.

Sometimes, the web daemon uses dedicated accounts, such as apache/www-data. In
that case the web daemon effectively has no rights to your files.

How to overcome this?

Solution 1. Impersonate your account

Ask your system administrator to run your scripts with your account information,
as explained before. See http://www.suphp.org for a practical solution.

Solution 2. Open to the world

You can attempt to 'chmod' everything to 707, to give maximum
permissions to the world, including the web daemon. Check actual results with
'ls -l', since your account may be prevented to do this. For example:

	cd your_installation_directory
	chmod -R 707 .
	ls -l

Solution 3. Use web daemon group

Another option is to change group information of all files,
to re-use the one of the web daemon. Then you will have to give maximum
permissions to the group. For example:

	cd your_installation_directory
	chgrp -R www-data .
	chmod -R 770 .

Solution 4. Set web daemon in your group

Ask your system administrator to put the web user account in your
own group, and then give maximum permissions to the group. For example:

	cd your_installation_directory
	chmod -R 770 .

Solution 5. Safe Mode

If the PHP environment is in safe mode, you should change that the
http daemon has the same uid (owner) than the owner of files and directories.
You can either ask your system administrator to impersonate your account(see
solution 1), or ask him to change ownership of the YACS directory.

Example allowed only to root users:

	cd your_installation_directory
	chown -R www-data .


For every other need
====================

Please look at http://www.yetanothercommunitysystem.com/

Enjoy!


GNU Lesser General Public License
=================================

YACS: PHP/MySQL Community and Content Management System (http://www.yetanothercommunitysystem.com/)
Copyright (C) 2003-2008 Paxer Conseil

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU Lesser General Public License as published by the Free
Software Foundation; either version 2 of the License, or (at your option)
any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
details.

You should have received a copy of the GNU Lesser General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

Alternatively, you may download a copy of this licence file from
http://www.gnu.org/copyleft/lesser.txt

Your possession of this software signifies that you agree to these terms.
Please delete your copy of this software if you don't agree to these terms.


