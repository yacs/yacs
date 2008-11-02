<?php
/**
 * a simple blog client
 *
 * This script checks the blog API
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see services/blog.php
 */
include_once '../shared/global.php';
include_once 'call.php';

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// load localized strings
i18n::bind('services');

// load the skin
load_skin('services');

// the title of the page
$context['page_title'] = i18n::s('Sample blog client');

// make a target
$target = '';
if(isset($_REQUEST['target']))
	$target = $_REQUEST['target'];
elseif(isset($context['host_name']))
	$target = $context['host_name'];
$user_name = isset($_REQUEST['user_name']) ? $_REQUEST['user_name'] : '';
$user_password = isset($_REQUEST['user_password']) ? $_REQUEST['user_password'] : '';

// display a specific form
$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
$label = i18n::s('Server address');
$input = '<input type="text" name="target" id="target" size="30" maxlength="128" value="'.encode_field($target).'" />'."\n"
	.' '.Skin::build_submit_button(i18n::s('Go'));
$hint = i18n::s('The name or the IP address of the yacs server');
$fields[] = array($label, $input, $hint);
$label = i18n::s('User name');
$input = '<input type="text" name="user_name" size="30" maxlength="128" value="'.encode_field($user_name).'" />';
$fields[] = array($label, $input);
$label = i18n::s('User password');
$input = '<input type="password" name="user_password" size="30" maxlength="128" value="'.$user_password.'" />';
$fields[] = array($label, $input);
$context['text'] .= Skin::build_form($fields);
$context['text'] .= '</div></form>';

// set the focus at the first field
$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
	.'$("target").focus();'."\n"
	.'// ]]></script>'."\n";

// do the test
if(isset($_REQUEST['target'])) {

	// call blog web service
	$url = 'http://'.$_REQUEST['target'].'/services/blog.php';

	// blogger.getUserInfo
	$context['text'] .= Skin::build_block('blogger.getUserInfo', 'title');
	$parameters = array('dummy_appkey', $user_name, $user_password);
	$result = Call::invoke($url, 'blogger.getUserInfo', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $name => $value)
			$context['text'] .= $name.': '.$value.BR."\n";
	} else
		$context['text'] .= $data;

	// blogger.getUsersBlogs
	$context['text'] .= Skin::build_block('blogger.getUsersBlogs', 'title');
	$parameters = array('dummy_appkey', $user_name, $user_password);
	$result = Call::invoke($url, 'blogger.getUsersBlogs', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $item) {
			$context['text'] .= "<p>";
			foreach($item as $name => $value)
				$context['text'] .= $name.': '.$value.BR."\n";
			$context['text'] .= "</p>\n";
			if(!isset($blogid))
				$blogid = $item['blogid'];
		}
	} else
		$context['text'] .= $data;
	if(!isset($blogid))
		$blogid = 1;

	// mt.getCategoryList
	$context['text'] .= Skin::build_block('mt.getCategoryList', 'title');
	$parameters = array($blogid, $user_name, $user_password);
	$result = Call::invoke($url, 'mt.getCategoryList', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $item) {
			$context['text'] .= "<p>";
			foreach($item as $name => $value)
				$context['text'] .= $name.': '.$value.BR."\n";
			$context['text'] .= "</p>\n";
			if(!isset($blogid))
				$blogid = $item['blogid'];
		}
	} else
		$context['text'] .= $data;
	if(!isset($blogid))
		$blogid = 1;

	// blogger.getRecentPosts
	$context['text'] .= Skin::build_block('blogger.getRecentPosts', 'title');
	$parameters = array('dummy_appkey', $blogid, $user_name, $user_password, 3);
	$result = Call::invoke($url, 'blogger.getRecentPosts', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $item) {
			$context['text'] .= "<p>";
			foreach($item as $name => $value) {
				if($name == 'dateCreated')
					$context['text'] .= $name.': '.Skin::build_date($value).BR."\n";
				else
					$context['text'] .= $name.': '.$value.BR."\n";
			}
			$context['text'] .= "</p>\n";
		}
	} else
		$context['text'] .= $data;

	// metaWeblog.getRecentPosts
	$context['text'] .= Skin::build_block('metaWeblog.getRecentPosts', 'title');
	$parameters = array($blogid, $user_name, $user_password, 3);
	$result = Call::invoke($url, 'metaWeblog.getRecentPosts', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $item) {
			$context['text'] .= "<p>";
			foreach($item as $name => $value) {
				if($name == 'dateCreated')
					$context['text'] .= $name.': '.Skin::build_date($value).BR."\n";
				else
					$context['text'] .= $name.': '.$value.BR."\n";
			}
			$context['text'] .= "</p>\n";
		}
	} else
		$context['text'] .= $data;

	// metaWeblog.getCategories
	$context['text'] .= Skin::build_block('metaWeblog.getCategories', 'title');
	$parameters = array($blogid, $user_name, $user_password);
	$result = Call::invoke($url, 'metaWeblog.getCategories', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	$categoryId1 = NULL;
	$categoryId2 = NULL;
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $item) {
			$context['text'] .= "<p>";
			foreach($item as $name => $value) {
				$context['text'] .= $name.': '.$value.BR."\n";
				if(!$categoryId1 && ($name == 'categoryId'))
					$categoryId1 = $value;
				elseif(!$categoryId2 && ($name == 'categoryId'))
					$categoryId2 = $value;
			}
			$context['text'] .= "</p>\n";
		}
	} else
		$context['text'] .= $data;

	// testing parsing capability
	$sample_message = 'title: ga bu zo meu'."\n"
		.'source: shadok'."\n"
		.'categories: foo, bar'."\n"
		."\n"
		.'<introduction>a dummy introduction at '.date("F j, Y, g:i a").'</introduction>hello'."\n\n".'world'."\n\n".'hello world again'."\n\n".'this is to ensure that messages with multiple lines are processed correctly';

	// blogger.newPost
	$context['text'] .= Skin::build_block('blogger.newPost', 'title');
	$parameters = array('dummy_appkey', $blogid, $user_name, $user_password, $codec->encode($sample_message, 'string'), TRUE);
	$result = Call::invoke($url, 'blogger.newPost', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= '?? ';
		foreach($data as $item) {
			$context['text'] .= "<p>".$item."</p>\n";
		}
	} else
		$context['text'] .= '"'.$data.'"';

	// testing parsing capability, again
	$sample_message = 'source: shadok'."\n"
		.'tags: foo, bar'."\n"
		."\n"
		.'<introduction>a dummy introduction at '.date("F j, Y, g:i a").'</introduction>ga bu zo meu <img src="/favicon.ico" alt="dummy image">';

	// metaWeblog.newMediaObject -- upload one image
	$context['text'] .= Skin::build_block('metaWeblog.newMediaObject', 'title');
	$parameters = array($blogid, $user_name, $user_password,
		array( 'name' => 'WindowsLiveWriter/bononyvajetapeunpetitpapier_FF6D/pointer_thumb.gif',
			'type' => 'image/gif',
			'bits' => '<base64>R0lGODlhIQAaAPIAAAoKLObm5v8AAP///w4LNg0NM8zMzAAAACH5BAEAAAYALAAAAAAhABoAAANxGFpM/jDKp2pgZeptV9sgVD1DaZ5omkpq65rsK8wvHNWzUJexm++DXitHewlVRJ3xJksWV0yfUwmF4KatY2pKRWlR3Gx0iFV9T8na2ZReWptd17r0280HdfUY6Cbx7Q4VAIMhhQQdAYMAhiGIiowgFQkAOw==</base64>'
		));
	$result = Call::invoke($url, 'metaWeblog.newMediaObject', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= "<p>";
		foreach($data as $name => $value) {
			$context['text'] .= $name.': '.$value.BR."\n";
		}
		$context['text'] .= "</p>\n";
	} else
		$context['text'] .= '"'.$data.'"';

	// metaWeblog.newPost -- with movable type extensions
	$context['text'] .= Skin::build_block('metaWeblog.newPost', 'title');
	$parameters = array($blogid, $user_name, $user_password,
		array( 'title' => $codec->encode('a dummy title at '.date("F j, Y, g:i a"), 'string'),
			'description' => $codec->encode($sample_message, 'string'),
			'category' => $codec->encode('dummy, test, ga bu zo meu', 'string'),
			'link' => '',
			'mt_keywords' => $codec->encode('dummy, test, ga bu zo meu', 'string'),
			'mt_excerpt' => 'this goes in introduction'
		), TRUE);
	$result = Call::invoke($url, 'metaWeblog.newPost', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= '?? ';
		foreach($data as $item) {
			$context['text'] .= "<p>".$item."</p>\n";
		}
	} else {
		$context['text'] .= '"'.$data.'"';
		$postid = $data;
	}

	//
	// the following sequence is what the Microsoft blogging tools do to detect blog template
	//

	// metaWeblog.newPost
	$context['text'] .= Skin::build_block('metaWeblog.newPost', 'title');
	$parameters = array($blogid, $user_name, $user_password,
		array( 'title' => $codec->encode('Temporary Post Used For Style Detection (d5138649-53e6-4b83-a3bb-a082fe5f5c36)', 'string'),
			'description' => $codec->encode('<p>This is a temporary post that was not deleted. Please delete this manually. (f5029687-914a-4f7c-af08-2983a2f5aa27)</p>', 'string'),
			'link' => '',
			'mt_keywords' => '',
			'mt_excerpt' => ''
		), FALSE);
	$result = Call::invoke($url, 'metaWeblog.newPost', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	$postid = NULL;
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= '?? ';
		foreach($data as $item) {
			$context['text'] .= "<p>".$item."</p>\n";
		}
	} else {
		$context['text'] .= '"'.$data.'"';
		$postid = $data;
	}

	// follow-up of this post
	if($postid) {

		// mt.setPostCategories
		$context['text'] .= Skin::build_block('mt.setPostCategories', 'title');
		$categories = array();
		if($categoryId1)
			$categories[] = array('categoryId' => $categoryId1);
		if($categoryId2)
			$categories[] = array('categoryId' => $categoryId2);
		$parameters = array($postid, $user_name, $user_password, $categories);
		$result = Call::invoke($url, 'mt.setPostCategories', $parameters, 'XML-RPC');
		$status = @$result[0];
		$data = @$result[1];

		// display call result
		if(!$status)
			$context['text'] .= 'status: '.$data;
		elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
			$context['text'] .= $data['faultString'];
		elseif(is_array($data)) {
			$context['text'] .= '?? ';
			foreach($data as $item) {
				$context['text'] .= "<p>".$item."</p>\n";
			}
		} else
			$context['text'] .= '"'.$data.'"';

		// mt.setPostCategories --the weird way
		$context['text'] .= Skin::build_block('mt.setPostCategories', 'title');
		$parameters = array($postid, $user_name, $user_password, array());
		$result = Call::invoke($url, 'mt.setPostCategories', $parameters, 'XML-RPC');
		$status = @$result[0];
		$data = @$result[1];

		// display call result
		if(!$status)
			$context['text'] .= 'status: '.$data;
		elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
			$context['text'] .= $data['faultString'];
		elseif(is_array($data)) {
			$context['text'] .= '?? ';
			foreach($data as $item) {
				$context['text'] .= "<p>".$item."</p>\n";
			}
		} else
			$context['text'] .= '"'.$data.'"';

		// metaWeblog.editPost
		$context['text'] .= Skin::build_block('metaWeblog.editPost', 'title');
		$parameters = array($postid, $user_name, $user_password,
			array( 'title' => $codec->encode('Temporary Post Used For Style Detection (d5138649-53e6-4b83-a3bb-a082fe5f5c36)', 'string'),
				'description' => $codec->encode('<p>This is a temporary post that was not deleted. Please delete this manually. (f5029687-914a-4f7c-af08-2983a2f5aa27)</p>', 'string'),
				'link' => '',
				'mt_keywords' => '',
				'mt_excerpt' => ''
			), TRUE);
		$result = Call::invoke($url, 'metaWeblog.editPost', $parameters, 'XML-RPC');
		$status = @$result[0];
		$data = @$result[1];

		// display call result
		if(!$status)
			$context['text'] .= 'status: '.$data;
		elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
			$context['text'] .= $data['faultString'];
		elseif(is_array($data)) {
			$context['text'] .= '?? ';
			foreach($data as $item) {
				$context['text'] .= "<p>".$item."</p>\n";
			}
		} else
			$context['text'] .= '"'.$data.'"';

		// blogger.deletePost
		$context['text'] .= Skin::build_block('blogger.deletePost', 'title');
		$parameters = array('dummy_appkey', $postid, $user_name, $user_password);
		$result = Call::invoke($url, 'blogger.deletePost', $parameters, 'XML-RPC');
		$status = @$result[0];
		$data = @$result[1];

		// display call result
		if(!$status)
			$context['text'] .= 'status: '.$data;
		elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
			$context['text'] .= $data['faultString'];
		elseif(is_array($data)) {
			$context['text'] .= '?? ';
			foreach($data as $item) {
				$context['text'] .= "<p>".$item."</p>\n";
			}
		} else
			$context['text'] .= '"'.$data.'"';

	}

	$sample_message = '<p><span>c\'est</span> pour <strong>montrer</strong> <i>comment</i> on peut bloguer directement depuis <a href="http://www.flock.com/">Flock</a> vers un serveur YACS.</p>

<p>jhdjh qlsdh lqhjsdq et les a&#231;&#231;&#233;nts, &#231;a passe comment ? et l\'&#8364; aussi ?<br/>
</p>

<p>sdsd qd qsd qds</p>

<p>al non ? Vivement les plug-ins, qu\'on <FONT size="6" face="Verdana" color="#808000">puisse</FONT> ajouter d\'autres effets.

</p>

<p>qsd qsd qds qjhgkjg k j<br/>
</p>

<a title="" href="http://127.0.0.1/yacs/articles/view.php/4920"><img src="http://127.0.0.1/yacs/files/section/2739/Nnuphars_thumb.jpg" alt="" /></a><!-- technorati tags begin --><p style="font-size:10px;text-align:right;">technorati tags:<a href="http://technorati.com/tag/un" rel="tag">un</a>, <a href="http://technorati.com/tag/tag" rel="tag">tag</a>, <a href="http://technorati.com/tag/xml" rel="tag">xml</a>, <a href="http://technorati.com/tag/rpc" rel="tag">rpc</a></p><!-- technorati tags end -->';

	// metaWeblog.newPost -- the Flock way
	$context['text'] .= Skin::build_block('metaWeblog.newPost', 'title');
	$parameters = array($blogid, $user_name, $user_password,
		array( 'title' => $codec->encode('a post from Flock', 'string'),
			'description' => $codec->encode($sample_message, 'string'),
			'mt_convert_breaks' => '',
			'mt_tb_ping_urls' => array( 'http://www.technorati.com' )
		), TRUE);
	$result = Call::invoke($url, 'metaWeblog.newPost', $parameters, 'XML-RPC');
	$status = @$result[0];
	$data = @$result[1];

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && isset($data['faultString']) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= '?? ';
		foreach($data as $item) {
			$context['text'] .= "<p>".$item."</p>\n";
		}
	} else {
		$context['text'] .= '"'.$data.'"';
		$postid = $data;
	}

}

// general help on this page
$help = sprintf(i18n::s('Go to the %s and check debug mode for <code>call</code> has been set, if you want to record HTTP requests and responses.'), Skin::build_link('services/configure.php', i18n::s('configuration panel for services'), 'shortcut'))."\n";
$context['aside']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

render_skin();

?>