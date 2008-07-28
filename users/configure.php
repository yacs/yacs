<?php
/**
 * change parameters for users
 *
 * @todo capture a list of e-mail to be notified when a new person registers (prepare for baby-sitting)
 * @todo allow for automatic lock of pages after some idle time
 * @todo add a parameter to limit total uploads by one user (UncleJam) -- give a positive number of bytes, and stop accepting files at zero
 * @todo add a parameter users_editor_tags for authorized tags for editors (ThierryP)
 * @todo add a set of predefined javascript links for pre-defined profiles
 *
 * This script will let you modify following parameters:
 *
 * [*] [code]users_allowed_tags[/code] - HTML tags allowed on posts from non-associates.
 *
 * [*] [code]users_authenticator[/code] - By default YACS authenticates users
 * locally. If set to some textual value, YACS will load and use the related
 * authenticator plugin, as defined in interface users/authenticator.php.
 *
 * [*] [code]users_default_editor[/code] - The default editor to select for new
 * users. The default value is to use a bare textarea, but FCKEditor and TinyMCE
 * are also available. A companion checkbox can be used in the configuration
 * form to force a global change of all user profiles.
 *
 * [*] [code]users_maximum_managed_sections[/code] - The maximum number of
 * sections that one member can create on his own. The default value is 0, which
 * means that users are prevented to extend their web space.
 *
 * [*] [code]users_with_alerts[/code] - By default YACS send an e-mail message
 * on each alert to some end user. If this parameter is set to 'Y', YACS will
 * detect present users and send them interactive alerts instead.
 *
 * [*] [code]users_with_anonymous_comments[/code] - If explicitly set to 'Y',
 * yacs will allow anonymous surfers to post comments to any public page.
 * By default, surfers are invited to register or to authenticate before posting.
 * However, on intranets, or for specific web sites you may have to enable
 * anonymous comments.
 *
 * [*] [code]users_with_avatars[/code] - Use avatars in the index of members.
 * By default YACS displays the same icon for every user profile.
 *
 * [*] [code]users_with_approved_members[/code] - If this parameter is
 * explicitly set to 'Y', yacs will register registrants as subscribers.
 * Associates will have to edit user profiles to explicit allow new members.
 * Also, when this parameter is set to 'Y', all new articles are flagged as
 * restricted (instead of public). You should depart from the default mode if
 * your server is mainly devoted to a closed set of people.
 *
 * [*] [code]users_with_auto_publish[/code] - If this parameter is explicitly set to 'Y', yacs will
 * publish every posted article and file, with no review at all by some associate.
 * Else any new posted article will be placed into the review queue; that's the default behaviour.
 * Also, by default files uploaded by members are restricted to other members.
 * You should depart from the default mode either if you are on an intranet (and if you trust other people from your organization)
 * or if you have correctly setup mail services (and have a chance to review posted pages quite swiftly).
 * Set this parameter to 'Y' to mimic the behaviour of a regular Wiki web site.
 *
 * [*] [code]users_with_email_display[/code] - If explicitly set to 'Y', yacs will
 * display every address used for e-mail and for instant messaging as well.
 * If set to 'R', only authenticated users will be able to read this information.
 * Else authenticated users can send an email, but the address is not shown; that's the default behaviour.
 * You should depart from the default mode only if you are on an intranet and want to leverage information placed into your server, or if you trust members enough to not spam others..
 *
 * [*] [code]users_with_email_validation[/code] - When explicitly set to 'Y', this parameter
 * asks for explicit validation of e-mail addresses mentioned during the registration process.
 * Else, and this is the default behaviour, e-mail addresses are made optional.
 * Change this parameter to improve on e-mail reliability.
 *
 * [*] [code]users_without_archiving[/code] - By default YACS archives published
 * articles in weekly and monthly categories. This is great for live sites, but
 * can be irrelevant for application-like sites.
 * In such situations set this parameter to 'Y' to avoid auto-archiving.
 *
 * [*] [code]users_without_file_overloads[/code] - By default YACS allows any
 * member to update files. This is great for open communities, where one person
 * can upload one file, and another person change it afterwards.
 * In other situations, it may be important to restrict file modifications to
 * their original authors. In such situations set this parameter to 'Y'.
 *
 * [*] [code]users_without_login_box[/code] - By default YACS adds a login box
 * to the interface. Change this parameter to 'Y' to avoid this.
 *
 * [*] [code]users_without_login_welcome[/code] - By default YACS confirms authentication after logon.
 * Change this parameter to 'Y' to streamline this additional step.
 *
 * [*] [code]users_without_private_pages[/code] - By default YACS allows
 * community members to create private pages.
 * Change this parameter to 'Y' to avoid this.
 *
 * [*] [code]users_without_registration[/code] - By default YACS accepts any new registrant.
 * If explicitly set to 'Y', new applications will be rejected.
 * Associates will have to create new user profiles when required.
 * Also, the user menu only appears to authenticated users.
 *
 * [*] [code]users_without_revision[/code] - By default YACS allows revisions after publication by authors.
 * If this parameter is explicitly set to 'Y', members may not modify their own posts after publication.
 * When set to 'Y', this parameter prevents posters to modify their submission in forums.
 * Note that members are always allowed to modify the comments, images, files, and links they have posted.
 *
 * [*] [code]users_without_robot_check[/code] - By default YACS adds random data in forms submitted by anonymous surfers.
 * If this parameter is explicitly set to 'Y', this check does not take place anymore, which ease the task on intranet servers.
 *
 * [*] [code]users_without_self_deletion[/code] - By default YACS allows users to delete their own profile.
 * If explicitly set to 'Y', only associates can delete user profiles.
 * This setting increases the traceability of user actions at the server.
 *
 * [*] [code]users_without_submission[/code] - By default authenticated members
 * are allowed to submit new articles and other material. If this parameter is
 * explicitly set to 'Y', only associates would be able to contribute.
 * Use this parameter to create a read-only site.
 * Also, the query form is always available.
 *
 * [*] [code]users_without_teasers[/code] - By default YACS shows teasers of restricted articles to non members.
 * Also links to post articles or comments are displayed in sections.
 * But if you set this parameter to 'Y', nothing of restricted articles will be shown to
 * anonymous surfers, and people will have to authenticate to read and post.
 *
 * [*] [code]users_without_uploads[/code] - By default YACS allows the upload of
 * images and files. If you want to administratively disable this, or if the
 * server complains on upload because of unsufficient rights on the file
 * system, set this parameter to 'Y'. YACS will then disable upload forms.
 *
 *
 * Configuration information is saved into [code]parameters/users.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/users.include.php.bak[/code] can be used to restore
 * the active configuration before the last change, if necessary.
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester NickR
 * @tester Lucrecius
 * @tester Jan Boen
 * @tester ThierryP
 * @tester Canardo69
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'users.php';

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('Configure: %s'), i18n::s('People'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('users/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif($_SERVER['REQUEST_METHOD'] != 'POST') {

	// load current parameters, if any
	Safe::load('parameters/users.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	//
	// registration management
	//
	$registration = '';
	$fields = array();

	// registration control
	$label = i18n::s('Registration');
	$input = '<input type="radio" name="users_without_registration" value="N"';
	if(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Any anonymous surfer may apply.');
	$input .= BR.'<input type="radio" name="users_without_registration" value="Y"';
	if(isset($context['users_without_registration']) && ($context['users_without_registration'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Only associates can create new user profiles.');
	$fields[] = array($label, $input);

	// e-mail control
	$label = i18n::s('E-mail');
	$input = '<input type="radio" name="users_with_email_validation" value="N"';
	if(!isset($context['users_with_email_validation']) || ($context['users_with_email_validation'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('New users are free to provide an e-mail address or not.');
	$input .= BR.'<input type="radio" name="users_with_email_validation" value="Y"';
	if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Registrants become members after validation of their e-mail. When this option is activated, associates cannot validate membership anymore.');
	$fields[] = array($label, $input);

	// membership control
	$label = i18n::s('Membership');
	$input = '<input type="radio" name="users_with_approved_members" value="N"';
	if(!isset($context['users_with_approved_members']) || ($context['users_with_approved_members'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Self-registrating surfer will become a member (open community).');
	$input .= BR.'<input type="radio" name="users_with_approved_members" value="Y"';
	if(isset($context['users_with_approved_members']) && ($context['users_with_approved_members'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Associates have to validate membership (closed community).');
	$fields[] = array($label, $input);

	// default editor
	$label = i18n::s('Default editor');
	$input = '<input type="radio" name="users_default_editor" value="tinymce"';
	if(!isset($context['users_default_editor']) || ($context['users_default_editor'] == 'tinymce'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('TinyMCE');
	$input .= BR.'<input type="radio" name="users_default_editor" value="fckeditor"';
	if(isset($context['users_default_editor']) && ($context['users_default_editor'] == 'fckeditor'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('FCKEditor');
	$input .= BR.'<input type="radio" name="users_default_editor" value="yacs"';
	if(isset($context['users_default_editor']) && ($context['users_default_editor'] == 'yacs'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Textarea');
	$input .= BR.'<input type="checkbox" name="force_editor_change" />'.i18n::s('Update all user profiles.');
	$fields[] = array($label, $input);

	// overlay
	$label = i18n::s('Extension');
	if(!isset($context['users_overlay']))
		$context['users_overlay'] = '';
	$input = '<input type="text" name="users_overlay" size="65" value="'.encode_field($context['users_overlay']).'" maxlength="128" />';
	$hint = sprintf(i18n::s('Script used to %s at this server'), Skin::build_link('overlays/', i18n::s('overlay user profiles'), 'help'));
	$fields[] = array($label, $input, $hint);

	// deletion control
	$label = i18n::s('Self-deletion');
	$input = '<input type="radio" name="users_without_self_deletion" value="N"';
	if(!isset($context['users_without_self_deletion']) || ($context['users_without_self_deletion'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Any registered surfer may delete his own profile.');
	$input .= BR.'<input type="radio" name="users_without_self_deletion" value="Y"';
	if(isset($context['users_without_self_deletion']) && ($context['users_without_self_deletion'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Only associates can delete user profiles.');
	$fields[] = array($label, $input);

	// build the form
	$registration .= Skin::build_form($fields);
	$fields = array();

	//
	// authentication management
	//
	$authentication = '';

	// login box
	$label = i18n::s('Authentication');
	$input = '<input type="radio" name="users_without_login_box" value="N"';
	if(!isset($context['users_without_login_box']) || ($context['users_without_login_box'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Offer anonymous surfers to login.');
	$input .= BR.'<input type="radio" name="users_without_login_box" value="Y"';
	if(isset($context['users_without_login_box']) && ($context['users_without_login_box'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Do not display this item.');
	$fields[] = array($label, $input);

	// anti-robot
	$label = i18n::s('Anti-robot');
	$input = '<input type="radio" name="users_without_robot_check" value="N"';
	if(!isset($context['users_without_robot_check']) || ($context['users_without_robot_check'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Adds random data to forms and block spam (public site).');
	$input .= BR.'<input type="radio" name="users_without_robot_check" value="Y"';
	if(isset($context['users_without_robot_check']) && ($context['users_without_robot_check'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Always accept anonymous input (intranet site).');
	$fields[] = array($label, $input);

	// default is to authenticate locally
	$custom_authenticator = '';
	if(isset($context['users_authenticator']) && strlen($context['users_authenticator'])) {
		$custom_authenticator = $context['users_authenticator'];
		$context['users_authenticator'] = 'custom';
	} else
		$context['users_authenticator'] = 'local';

	// authentication control
	$label = i18n::s('Screening');
	$input = '<input type="radio" name="users_authenticator" value="local"';
	if($context['users_authenticator'] == 'local')
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Login information is checked locally.');
	$input .= BR.'<input type="radio" name="users_authenticator" value="custom"';
	if($context['users_authenticator'] != 'local')
		$input .= ' checked="checked"';
	$input .= EOT.' '.sprintf(i18n::s('Use the authenticator %s'), '<input type="text" name="users_custom_authenticator" value="'.encode_field($custom_authenticator).'" size="80"'.EOT);
	$hint = i18n::s('Provide adequate parameters after authenticator type.');
	$fields[] = array($label, $input, $hint);

	// redirection control
	$label = i18n::s('Welcome');
	$input = '<input type="radio" name="users_without_login_welcome" value="N"';
	if(!isset($context['users_without_login_welcome']) || ($context['users_without_login_welcome'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Display welcome panel on successful authentication.');
	$input .= BR.'<input type="radio" name="users_without_login_welcome" value="Y"';
	if(isset($context['users_without_login_welcome']) && ($context['users_without_login_welcome'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Redirect directly to protected page after authentication.');
	$fields[] = array($label, $input);

	// permanent authentication
	$label = i18n::s('Identification');
	$input = '<input type="radio" name="users_with_permanent_authentication" value="N"';
	if(!isset($context['users_with_permanent_authentication']) || ($context['users_with_permanent_authentication'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Ask for authentication on every web session (public site).');
	$input .= BR.'<input type="radio" name="users_with_permanent_authentication" value="Y"';
	if(isset($context['users_with_permanent_authentication']) && ($context['users_with_permanent_authentication'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Set a long-lasting cookie on successful login and do not bother people afterwards (intranet site).');
	$fields[] = array($label, $input);

	// build the form
	$authentication .= Skin::build_form($fields);
	$fields = array();

	//
	// content management
	//
	$content = '';

	// alert present users
	$label = i18n::s('Alerts');
	$input = '<input type="radio" name="users_with_alerts" value="N"';
	if(!isset($context['users_with_alerts']) || ($context['users_with_alerts'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Watchers are notified by e-mail.');
	$input .= BR.'<input type="radio" name="users_with_alerts" value="Y"';
	if(isset($context['users_with_alerts']) && ($context['users_with_alerts'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Users receive interactive popups when they are present.');
	$fields[] = array($label, $input);

	// users_with_avatars
	$label = i18n::s('Avatars');
	$input = '<input type="radio" name="users_with_avatars" value="N"';
	if(!isset($context['users_with_avatars']) || ($context['users_with_avatars'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Display the same icon for every member profile in user index.');
	$input .= BR.'<input type="radio" name="users_with_avatars" value="Y"';
	if(isset($context['users_with_avatars']) && ($context['users_with_avatars'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Enhance the user index with member avatars.');
	$fields[] = array($label, $input);

	// spam protection
	$label = i18n::s('Spam');
	$input = '<input type="radio" name="users_with_email_display" value="N"';
	if(!isset($context['users_with_email_display']) || ($context['users_with_email_display'] == 'N'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Protect mail addresses as much as possible (for Internet servers).');
	$input .= BR.'<input type="radio" name="users_with_email_display" value="R"';
	if(isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'R'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Show email addresses to authenticated members.');
	$input .= BR.'<input type="radio" name="users_with_email_display" value="Y"';
	if(isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Share email addresses as much as possible (for intranet servers).');
	$fields[] = array($label, $input);

	// without private pages
	$label = i18n::s('Private pages');
	$input = '<input type="radio" name="users_without_private_pages" value="N"';
	if(!isset($context['users_without_private_pages']) || ($context['users_without_private_pages'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Facilitate contacts between community members through shared private pages.');
	$input .= BR.'<input type="radio" name="users_without_private_pages" value="Y"';
	if(isset($context['users_without_private_pages']) && ($context['users_without_private_pages'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Prevent members to create private pages.');
	$fields[] = array($label, $input);

	// users_maximum_managed_sections
	if(!isset($context['users_maximum_managed_sections']) || !$context['users_maximum_managed_sections'] || ($context['users_maximum_managed_sections'] < 0))
		$context['users_maximum_managed_sections'] = 0;
	$label = i18n::s('Personal');
	$input = sprintf(i18n::s('Each member may self-manage up to %s personal spaces.'), '<input type="text" name="users_maximum_managed_sections" size="2" value="'.encode_field($context['users_maximum_managed_sections']).'" maxlength="2" />');
	$hint = i18n::s('We recommend either 0 (members cannot extend their web space), or 3 (public, restricted, hidden).');
	$fields[] = array($label, $input, $hint);

	// without teasers
	$label = i18n::s('Teasers');
	$input = '<input type="radio" name="users_without_teasers" value="N"';
	if(!isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Anonymous surfers can see titles and teasers of restricted articles, and links to post content. They can move forward after authentication.');
	$input .= BR.'<input type="radio" name="users_without_teasers" value="Y"';
	if(isset($context['users_without_teasers']) && ($context['users_without_teasers'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Nothing from restricted pages and links to post is disclosed to non-members.');
	$fields[] = array($label, $input);

	// auto-archiving
	$label = i18n::s('Archiving');
	$input = '<input type="radio" name="users_without_archiving" value="N"';
	if(!isset($context['users_without_archiving']) || ($context['users_without_archiving'] == 'N'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Published pages are automatically assigned to weekly and monthly categories.');
	$input .= BR.'<input type="radio" name="users_without_archiving" value="Y"';
	if(isset($context['users_without_archiving']) && ($context['users_without_archiving'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Automatic archiving is disabled.');
	$fields[] = array($label, $input);

	// build the form
	$content .= Skin::build_form($fields);
	$fields = array();

	//
	// submission management
	//
	$submission = '';

	// allowed tags
	$label = i18n::s('Allowed Tags');
	$input = '<input type="text" name="users_allowed_tags" size="65" value="'.encode_field($context['users_allowed_tags']).'" maxlength="128" />';
	$hint = i18n::s('List HTML tags allowed to members.').BR.'&lt;a&gt;&lt;abbr&gt;&lt;acronym&gt;&lt;b&gt;&lt;big&gt;&lt;br&gt;&lt;code&gt;&lt;dd&gt;&lt;del&gt;&lt;dfn&gt;&lt;dl&gt;&lt;dt&gt;&lt;em&gt;&lt;i&gt;&lt;img&gt;&lt;ins&gt;&lt;li&gt;&lt;ol&gt;&lt;p&gt;&lt;q&gt;&lt;small&gt;&lt;span&gt;&lt;strong&gt;&lt;sub&gt;&lt;sup&gt;&lt;tt&gt;&lt;u&gt;&lt;ul&gt;';
	$fields[] = array($label, $input, $hint);

	// submission control
	$label = i18n::s('Submissions');
	$input = '<input type="radio" name="users_without_submission" value="N"';
	if(!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Authenticated members are allowed to submit new articles.');
	$input .= BR.'<input type="radio" name="users_without_submission" value="Y"';
	if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Only associates are allowed to add pages. Members can post comments.');
	$fields[] = array($label, $input);

	// content publishing
	$label = i18n::s('Publications');
	$input = '<input type="radio" name="users_with_auto_publish" value="N"';
	if(!isset($context['users_with_auto_publish']) || ($context['users_with_auto_publish'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Members submit articles and associates publish them (review mode), except in sections configured with option <code>auto_publish</code>.');
	$input .= BR.'<input type="radio" name="users_with_auto_publish" value="Y"';
	if(isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Every post is published (Wiki mode).');
	$fields[] = array($label, $input);

	// revision control
	$label = i18n::s('Revisions');
	$input = '<input type="radio" name="users_without_revision" value="N"';
	if(!isset($context['users_without_revision']) || ($context['users_without_revision'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Authenticated members are allowed to modify articles they have posted.');
	$input .= BR.'<input type="radio" name="users_without_revision" value="Y"';
	if(isset($context['users_without_revision']) && ($context['users_without_revision'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Revisions are forbidden after publication, except from associates.');
	$fields[] = array($label, $input);

	// upload control
	$label = i18n::s('Uploads');
	if(!ini_get('file_uploads')) {
		$input = i18n::s('The PHP run-time configuration does not allow for any upload.')
			.'<input type="hidden" name="users_without_uploads" value="Y" />';
	} else {
		$input = '<input type="radio" name="users_without_uploads" value="N"';
		if(!isset($context['users_without_uploads']) || ($context['users_without_uploads'] != 'Y'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Authenticated members are allowed to post images and files.');
		$input .= BR.'<input type="radio" name="users_without_uploads" value="R"';
		if(isset($context['users_without_uploads']) && ($context['users_without_uploads'] == 'R'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Only associates are allowed to post images and files.');
		$input .= BR.'<input type="radio" name="users_without_uploads" value="Y"';
		if(isset($context['users_without_uploads']) && ($context['users_without_uploads'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('No upload is accepted.');
	}
	$fields[] = array($label, $input);

	// control of file overload
	$label = i18n::s('Files');
	$input = '<input type="radio" name="users_without_file_overloads" value="N"';
	if(!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Any member can update files posted by others.');
	$input .= BR.'<input type="radio" name="users_without_file_overloads" value="R"';
	if(isset($context['users_without_file_overloads']) && ($context['users_without_file_overloads'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Files can be modified only by their original authors, plus associates and editors.');
	$fields[] = array($label, $input);

	// with anonymous comments
	$label = i18n::s('Comments');
	$input = '<input type="radio" name="users_with_anonymous_comments" value="N"';
	if(!isset($context['users_with_anonymous_comments']) || ($context['users_with_anonymous_comments'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Only authenticated surfers can send comments.');
	$input .= BR.'<input type="radio" name="users_with_anonymous_comments" value="Y"';
	if(isset($context['users_with_anonymous_comments']) && ($context['users_with_anonymous_comments'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Allow anonymous surfers to post comments.');
	$fields[] = array($label, $input);

	// build the form
	$submission .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('registration_tab', i18n::s('Registration'), 'registration_panel', $registration),
		array('authentication_tab', i18n::s('Authentication'), 'authentication_panel', $authentication),
		array('content_tab', i18n::s('Content'), 'content_panel', $content),
		array('submission_tab', i18n::s('Submission'), 'submission_panel', $submission)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// all users
	$menu[] = Skin::build_link('users/', i18n::s('People'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation in demonstration mode.').'</p>'."\n";

// save updated parameters
} else {

	// change all user profiles at once
	if(isset($_REQUEST['users_default_editor']) && isset($_REQUEST['force_editor_change'])) {
		if(Users::change_all(array('editor' => $_REQUEST['users_default_editor'])))
			$context['text'] .= '<p>'.i18n::s('All user profiles have been updated.')."</p>\n";

		// save new editor settings in surfer session
		$_SESSION['surfer_editor'] = $_REQUEST['users_default_editor'];
	}



	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/users.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/users.include.php', $context['path_to_root'].'parameters/users.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script users/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";
	if(isset($_REQUEST['users_allowed_tags']))
		$content .= '$context[\'users_allowed_tags\']=\''.addcslashes($_REQUEST['users_allowed_tags'], "\\'")."';\n";
	if(isset($_REQUEST['users_custom_authenticator']))
		$content .= '$context[\'users_authenticator\']=\''.addcslashes($_REQUEST['users_custom_authenticator'], "\\'")."';\n";
	if(isset($_REQUEST['users_default_editor']))
		$content .= '$context[\'users_default_editor\']=\''.addcslashes($_REQUEST['users_default_editor'], "\\'")."';\n";
	if(!isset($_REQUEST['users_maximum_managed_sections']))
		$_REQUEST['users_maximum_managed_sections'] = 0;
	$content .= '$context[\'users_maximum_managed_sections\']=\''.addcslashes($_REQUEST['users_maximum_managed_sections'], "\\'")."';\n";
	if(isset($_REQUEST['users_overlay']))
		$content .= '$context[\'users_overlay\']=\''.addcslashes($_REQUEST['users_overlay'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_alerts']))
		$content .= '$context[\'users_with_alerts\']=\''.addcslashes($_REQUEST['users_with_alerts'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_avatars']))
		$content .= '$context[\'users_with_avatars\']=\''.addcslashes($_REQUEST['users_with_avatars'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_anonymous_comments']))
		$content .= '$context[\'users_with_anonymous_comments\']=\''.addcslashes($_REQUEST['users_with_anonymous_comments'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_approved_members']))
		$content .= '$context[\'users_with_approved_members\']=\''.addcslashes($_REQUEST['users_with_approved_members'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_auto_publish']))
		$content .= '$context[\'users_with_auto_publish\']=\''.addcslashes($_REQUEST['users_with_auto_publish'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_email_display']))
		$content .= '$context[\'users_with_email_display\']=\''.addcslashes($_REQUEST['users_with_email_display'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_email_validation']))
		$content .= '$context[\'users_with_email_validation\']=\''.addcslashes($_REQUEST['users_with_email_validation'], "\\'")."';\n";
	if(isset($_REQUEST['users_with_permanent_authentication']))
		$content .= '$context[\'users_with_permanent_authentication\']=\''.addcslashes($_REQUEST['users_with_permanent_authentication'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_archiving']))
		$content .= '$context[\'users_without_archiving\']=\''.addcslashes($_REQUEST['users_without_archiving'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_file_overloads']))
		$content .= '$context[\'users_without_file_overloads\']=\''.addcslashes($_REQUEST['users_without_file_overloads'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_login_box']))
		$content .= '$context[\'users_without_login_box\']=\''.addcslashes($_REQUEST['users_without_login_box'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_login_welcome']))
		$content .= '$context[\'users_without_login_welcome\']=\''.addcslashes($_REQUEST['users_without_login_welcome'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_private_pages']))
		$content .= '$context[\'users_without_private_pages\']=\''.addcslashes($_REQUEST['users_without_private_pages'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_revision']))
		$content .= '$context[\'users_without_revision\']=\''.addcslashes($_REQUEST['users_without_revision'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_registration']))
		$content .= '$context[\'users_without_registration\']=\''.addcslashes($_REQUEST['users_without_registration'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_robot_check']))
		$content .= '$context[\'users_without_robot_check\']=\''.addcslashes($_REQUEST['users_without_robot_check'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_self_deletion']))
		$content .= '$context[\'users_without_self_deletion\']=\''.addcslashes($_REQUEST['users_without_self_deletion'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_submission']))
		$content .= '$context[\'users_without_submission\']=\''.addcslashes($_REQUEST['users_without_submission'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_teasers']))
		$content .= '$context[\'users_without_teasers\']=\''.addcslashes($_REQUEST['users_without_teasers'], "\\'")."';\n";
	if(isset($_REQUEST['users_without_uploads']))
		$content .= '$context[\'users_without_uploads\']=\''.addcslashes($_REQUEST['users_without_uploads'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/users.include.php', $content)) {

		Skin::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/users.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/users.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/users.include.php')."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/users.include.php');
		Logger::remember('users/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'users/' => i18n::s('People') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'users/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>