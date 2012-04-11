<?php
/**
 * parse PHP scripts to build the on-line documentation
 *
 * This class is derived from previous work originally coming from the Java world, by which
 * developers have found a mean to auto-document their files.
 *
 * Some PHP developers have selected similar rules to achieve the same result with scripts,
 * and I must admit that this (very simple) solution may deliver sensible results with ease.
 *
 * - Are you able to produce or to alter some PHP code?
 * - Would you like to achieve a simple view of all these files?
 * - What about reading some pretty HTML before browsing the code itself?
 * - Oh yes! Maybe you would like to link things together (e.g. 'see also this script...')
 * - Do you want to locate easily scripts on your to-do list?
 * - Are you tracking software contributors to better reward them?
 * - Are you dreaming of locating some information through full-text search?
 *
 * If you have answered yes to a couple of these questions, then you'd better read the end of this page.
 *
 * [title]Documentation blocks[/title]
 * The documentation process begins with the most basic element of phpDoc: a documentation block.
 * A basic documentation block looks like this:
 * [snippet]
 * &#47;**
 *	*
 *	*&#47;
 * [/snippet]
 *
 * A documentation block is an extended C++-style PHP comment that begins with "&#47;**" and has an "*" at the beginning of every line.
 * documentation blocks precede the element they are documenting. To document function "foo()", type:
 * [snippet]
 * &#47;**
 * * Defies imagination, extends boundaries and saves the world ...all before breakfast!
 *	*&#47;
 * function foo()
 * {
 * }
 * [/snippet]
 *
 * A documentation block contains three basic segments in this order:
 * - short description
 * - long description
 * - tags
 *
 * The short description is the very first text line of the documentation block.
 * The long description continues for as many lines as desired and may contain html markup or yacs commands for display formatting.
 * Here is a sample documentation block with a short and a long description:
 * [snippet]
 * &#47;**
 * * return the date of Easter
 * *
 * * Using the formula from "Formulas that are way too complicated for anyone to
 * * ever understand except for me" by Irwin Nerdy, this function calculates the
 * * date of Easter given a date in the Ancient Mayan Calendar, if you can also
 * * guess the birthday of the author.
 *	*&#47;
 * [/snippet]
 *
 * [title]Tags[/title]
 * Tags are single words prefixed by a "&#64;" symbol.
 * Tags provide additional information used to build the final documentation.
 * All tags are optional, but if you use a tag, they do have specific requirements to parse properly.
 *
 * The following snippet is a sample documentation block showing all possible tags.
 * More tags may be added in the future, not all tags are implemented at this time in YACS, however they are all recognized as tags and will at least be displayed.
 * [snippet]
 * &#47;**
 * * The short description
 * *
 * * As many lines of extendend description as you want.
 * * Below this goes the tags to further describe element you are documenting.
 * *
 * * &#64;param 	type	description
 * * &#64;return	type	description
 * * &#64;author	author name url or email or text
 * * &#64;tester	tester name url or email or text
 * * &#64;license	a url and an optional description
 * * &#64;reference (if this tag is present, this script has to be copied to synchronizing servers)
 * * &#64;see		link to the documentation page of another script
 * * &#64;link	http://www.example.com Example hyperlink inline link
 * * &#64;todo		a verb and some words to describe a future action
 * *&#47;
 * [/snippet]
 *
 * [title]Examples[/title]
 * A sample documentation block placed at the beginning of a script:
 * [snippet]
 * &#47;**
 * * parse PHP scripts to build the on-line documentation
 * *
 * * This class is derived from previous work originally coming from the Java world, by which
 * * developers have found a mean to auto-document their files.
 * ...
 * *
 * * &#64;todo add tags since and deprecated
 * * &#64;see scripts/build.php
 * * &#64;link http://phpdoc.org/ phpDoc web page
 * * &#64;link http://sourceforge.net/projects/phpdocu project page
 * * &#64;author Bernard Paques
 * * &#64;author Foo Bar [link]http://foo.bar.com/[/link]
 * * &#64;tester Guillaume Perez
 * * &#64;reference
 * * &#64;license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * *&#47;
 *
 * class PhpDoc {
 *	 ...
 * [/snippet]
 *
 * A sample documentation block for a function:
 * [snippet]
 * &#47;**
 * * return the day of the week
 * *
 * * &#64;param string 3-letter Month abbreviation
 * * &#64;param integer day of the month
 * * &#64;param integer year
 * * &#64;return integer 0 = Sunday, value returned is from 0 (Sunday) to 6 (Saturday)
 * *&#47;
 * function day_week($month, $day, $year)
 * {
 * ...
 * }
 * [/snippet]
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class PhpDoc {

	// the index of documented scripts
	var $index;

	// the main comment block for each script
	var $comments;

	// the authors
	var $authors;

	// the testers
	var $testers;

	// the to-do list
	var $todos;

	// the licenses list
	var $licenses;

	/**
	 * process one comment block (internal use)
	 *
	 * @param string the script file name
	 * @param string the block signature
	 * @param array the comment
	 * @return the string to insert into the resulting page
	 */
	function comment_block($script, $signature, $comment) {
		global $context;

		$authors = array();
		$licenses = array();
		$links = array();
		$result = '';
		$testers = array();
		$todos = array();
		$types = array();
		$first_line = '';
		$subsequent_lines = '';

		// parse the comment line by line
		if(isset($comment) && is_array($comment) && @count($comment)) {
			foreach($comment as $line) {

				// &#64;param type whatever
				if(preg_match('/@param\s+(.+)$/i', $line, $matches))
					$types[] = $matches[1];

				// &#64;return type whatever
				elseif(preg_match('/@return\s+(.+)$/i', $line, $matches))
					$result = $matches[1];

				// &#64;see url label
				elseif(preg_match('/@see\s+(.+?)\s+(.+)$/i', $line, $matches))
					$links[] = '[script='.$matches[2].']'.trim($matches[1]).'[/script]';

				// &#64;see url
				elseif(preg_match('/@see\s+(.+?)$/i', $line, $matches))
					$links[] = '[script]'.trim($matches[1]).'[/script]';

				// &#64;license url label
				elseif(preg_match('/@license\s+(.+?)\s+(.+)$/i', $line, $matches)) {
					$licenses[] = '[link='.$matches[2].']'.trim($matches[1]).'[/link]';
					$this->licenses[trim($matches[1])][] = $script;

				// &#64;license url
				} elseif(preg_match('/@license\s+(.+)$/i', $line, $matches)) {
					$licenses[] = '[link]'.trim($matches[1]).'[/link]';
					$this->licenses[trim($matches[1])][] = $script;

				// &#64;link url label
				} elseif(preg_match('/@link\s+(.+?)\s+(.+)$/i', $line, $matches))
					$links[] = '[link='.$matches[2].']'.trim($matches[1]).'[/link]';

				// &#64;link url
				elseif(preg_match('/@link\s+(.+)$/i', $line, $matches))
					$links[] = '[link]'.trim($matches[1]).'[/link]';

				// &#64;todo any text
				elseif(preg_match('/@todo\s+(.+)$/i', $line, $matches)) {
					$todos[] = $matches[1];
					$this->todos[$script][] = $matches[1];
				}

				// &#64;author any text
				elseif(preg_match('/@author\s+(.+)$/i', $line, $matches)) {
					$authors[] = trim($matches[1]);
					$this->authors[trim($matches[1])][] = $script;
				}

				// &#64;tester any text
				elseif(preg_match('/@tester\s+(.+)$/i', $line, $matches)) {
					$testers[] = trim($matches[1]);
					$this->testers[trim($matches[1])][] = $script;
				}

				// &#64;reference
				elseif(preg_match('/@reference/i', $line, $matches))
					$subsequent_lines .= '<p>This script is a reference file of this system.</p>';

				// first line
				elseif(!$first_line)
					$first_line = ' - '.ucfirst(rtrim($line))."\n";

				// regular line
				else
					$subsequent_lines .= rtrim($line)."\n";
			}
		}

		// layout the comment block
		$text = '';

		// something with parameters
		if(preg_match('/(.+)\s+(.+)\s*\((.*)\)/s', trim($signature), $matches)) {

			// extract the block name
			$name = ltrim($matches[2], "&").'()';;
			$parameters = preg_split('/[\s,]+/', $matches[3], -1, PREG_SPLIT_NO_EMPTY);

			// name and first line as a title
			$title = $name.$first_line;
			$text .= '[title]'.$title.'[/title]';

			// block signature
			$text .= '<p>'.trim($signature);

			// parameters
			if(@count($parameters)) {
				$index = 0;
				foreach($parameters as $parameter) {
					$type = isset($types[$index]) ? $types[$index] : '';
					$text .= "\n".'- '.$parameter.' - '.$type;
					$index++;
				}
			}

			// result
			if($result)
				$text .= "\n".'- returns '.$result;

			$text .= '</p>';

		// something without parameters
		} elseif($signature) {

			// signature and first line as a title
			$title = $signature.$first_line;
			$text .= '[title]'.$title.'[/title]';

		// an anonymous block
		} else {

			$title = $script.$first_line;
			$text .= '[title]'.$title.'[/title]';

		}

		// this comment was for the file
		global $first_comment;
		if($first_comment) {
			$this->index[$script] = $script.$first_line;
			$first_comment = FALSE;
		}

		// subsequent lines
		$text .= $subsequent_lines;

		// &#64;link
		if(count($links)) {
			$text .= '<p>'.i18n::s('See also:')."\n";
			foreach($links as $link)
				$text .= '- '.$link."\n";
			$text .= "</p>\n";
		}

		// &#64;license
		if(count($licenses)) {
			$text .= '<p>'.i18n::s('License:');
			foreach($licenses as $license)
				$text .= ' '.$license.' ';
			$text .= "</p>\n";
		}

		// &#64;author
		if(count($authors)) {
			$text .= '<p>'.i18n::s('Authors:')."\n";
			foreach($authors as $author)
				$text .= '- '.$author."\n";
			$text .= "</p>\n";
		}

		// &#64;tester
		if(count($testers)) {
			$text .= '<p>'.i18n::s('Testers:')."\n";
			foreach($testers as $tester)
				$text .= '- '.$tester."\n";
			$text .= "</p>\n";
		}

		// &#64;todo
		if(count($todos)) {
			$text .= '<p>'.i18n::s('On the to-do list:')."\n";
			foreach($todos as $todo)
				$text .= '- '.$todo."\n";
			$text .= "</p>\n";
		}

		// the full comment
		if(!isset($this->comments[$script]))
			$this->comments[$script] = '';
		$this->comments[$script] .= $text;
	}

	/**
	 * create the set of documentation pages
	 */
	function generate() {
		global $context, $page_count;

		if(!is_array($this->index)) {
			return i18n::s('No comments to report on!');
		}

		// animate the screen
		$context['text'] .= i18n::s('Generating script documentation, please wait...')."\n"."\n";

		// alphabetical order
		if(is_array($this->comments)) {
			reset($this->comments);
			ksort($this->comments);
		}

		// process each script
		$home = '';
		$index = '';
		$previous_path = '***';
		$page_count = 0;
		foreach($this->comments as $script => $comment) {

			// extract first directory from path information
			$path = array_shift(preg_split('/\//', dirname($script)));

			// build links to view the documentation
			if($context['with_friendly_urls'] == 'Y') {
				$link = 'scripts/view.php/'.$script;
				if($path)
					$index_link = 'scripts/view.php/'.$path.'/index.php';
				else
					$index_link = 'scripts/view.php/index.php';
			} else {
				$link = 'scripts/view.php?script='.$script;
				if($path)
					$index_link = 'scripts/view.php?script='.$path.'/index.php';
				else
					$index_link = 'scripts/view.php?script=index.php';
			}

			// extract first directory from path information
			$path = array_shift(preg_split('/\//', dirname($script)));
			if($path && ($path != '.') && ($path != $previous_path)) {
				if($index)
					$index .= "</ul><p></p></dd>\n";
				$index .= '<dt>'.Skin::build_link($index_link, ucfirst($path))."</dt>\n<dd><ul>";
				$previous_path = $path;
			}

			// some text for the index page
			if($path == '.') {
				$home .= '<li>'.Skin::build_link($link, $this->index[$script])."</li>\n";
			} else {
				$index .= '<li>'.Skin::build_link($link, $this->index[$script])."</li>\n";
			}

		}
		if($index)
			$index .= "</ul><p></p></dd>\n";

		// format the index page
		if($home) {
			if($context['with_friendly_urls'] == 'Y')
				$link = 'scripts/view.php/index.php';
			else
				$link = 'scripts/view.php?script=index.php';
			$index = '<dt>'.Skin::build_link($link, 'Home')."</dt>\n<dd><ul>".$home."</ul><p></p></dd>\n".$index;
		}
		$page = '<dl>'.$index."</dl>\n";

		// the index page
		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='index',"
			." content='".SQL::escape($page)."',"
			." edit_date='".$context['now']."'";
		if(SQL::query($query, TRUE) === FALSE)
			$context['text'] .= $query.BR.SQL::error().BR."\n";
		$page_count++;

		// the authors page
		if(is_array($this->authors)) {
			reset($this->authors);
			asort($this->authors);
		}
		$authors = '';
		foreach($this->authors as $author => $scripts) {
			if($authors)
				$authors .= "<p></p></dd>\n";
			$authors .= '<dt>'.$author."</dt>\n<dd>";
			reset($scripts);
			asort($scripts);
			$authors .= join(' ', $scripts)."\n";
		}
		if($authors)
			$authors .= "<p></p></dd>\n";
		$authors = "<dl>\n".$authors."</dl>\n";
		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='authors',"
			." content='".SQL::escape($authors)."',"
			." edit_date='".$context['now']."'";
		if(SQL::query($query, TRUE) === FALSE)
			return $query.BR.SQL::error();

		// the testers page
		if(is_array($this->testers)) {
			reset($this->testers);
			asort($this->testers);
		}
		$testers = '';
		if($this->testers) {
			foreach($this->testers as $tester => $scripts) {
				if($testers)
					$testers .= "<p></p></dd>\n";
				$testers .= '<dt>'.$tester."</dt>\n<dd>";
				reset($scripts);
				asort($scripts);
				$testers .= join(' ', $scripts)."\n";
			}
		}
		if($testers)
			$testers .= "<p></p></dd>\n";
		$testers = "<dl>\n".$testers."</dl>\n";
		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='testers',"
			." content='".SQL::escape($testers)."',"
			." edit_date='".$context['now']."'";
		if(SQL::query($query, TRUE) === FALSE)
			return $query.BR.SQL::error();

		// the licenses page
		if(is_array($this->licenses)) {
			reset($this->licenses);
			asort($this->licenses);
		}
		$licenses = '';
		foreach($this->licenses as $license => $scripts) {
			if($licenses)
				$licenses .= "<p></p></dd>\n";
			$licenses .= '<dt><a href="'.$license.'">'.$license."</a></dt>\n<dd>";
			reset($scripts);
			asort($scripts);
			$licenses .= join(' ', $scripts)."\n";
		}
		if($licenses)
			$licenses .= "<p></p></dd>\n";
		$licenses = "<dl>\n".$licenses."</dl>\n";
		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='licenses',"
			." content='".SQL::escape($licenses)."',"
			." edit_date='".$context['now']."'";
		if(SQL::query($query, TRUE) === FALSE)
			return $query.BR.SQL::error();

		// the todo page
		if(is_array($this->todos)) {
			reset($this->todos);
			ksort($this->todos);
		}
		$todos = '';
		if($this->todos) {
			foreach($this->todos as $script => $todo) {
				$todos .= "<h2>".$script."</h2>\n"
					."<ul>\n";
				foreach($todo as $line)
					$todos .= '<li>'.$line."</li>\n";
				$todos .= "</ul>\n";
			}
		}
		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='todo',"
			." content='".SQL::escape($todos)."',"
			." edit_date='".$context['now']."'";
		if(SQL::query($query, TRUE) === FALSE)
			$context['text'] .= $query.BR.SQL::error().BR."\n";
		$page_count++;

		$context['text'] .= 'Documentation pages have been generated.'.BR.BR."\n";
	}

	/**
	 * get one comment
	 *
	 * @param string the name of the comment to fetch
	 * @return the resulting $row array, with at least keys: 'name', 'anchor' and 'content'
	 */
	public static function &get($name) {
		global $context;

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('phpdoc')." AS phpdoc "
			." WHERE phpdoc.name = '".SQL::escape($name)."'";
		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * parse one script to build the php documentation
	 *
	 * @param string one script
	 * @param the path to access the script
	 * @return either NULL or an error message
	 */
	function parse($script, $path='scripts/reference/') {
		global $context, $page_count;

		// at least put the script name as a title
		$this->titles[$script] = $script;

		// read the file
		if(!$handle = Safe::fopen($path.$script, 'rb')) {
			$this->comments[$script] = sprintf(i18n::s('Impossible to read %s.'), $script);
			return sprintf(i18n::s('Impossible to read %s.'), $context['path_to_root'].$path.$script);
		}

		// locate php comments
		$comment = array();
		global $first_comment;
		$first_comment = TRUE;
		$in_comment = FALSE;
		$count = 0;
		while(!feof($handle)) {

			// up to 4k per line
			$line = fgets($handle, 4096);

			// ensure we have enough execution time
			$count++;
			if(!($count%1000))
				Safe::set_time_limit(30);

			// a comment ends
			if($in_comment && preg_match('/\s*\*+\//', $line)) {
				$in_comment = FALSE;

			// a comment continues
			} elseif($in_comment) {

				// strip the '*' at the beginning of the line
				$comment[] = preg_replace('/^\s*\*\s{0,1}/', '', $line);

			// comment begins
			} elseif(preg_match('/\s*\/\*{2,}/', $line)) {
				$in_comment = TRUE;

			// class extension
			} elseif(preg_match('/^\s*class\s+(\w+)\s+extends\s+(\w+)/i', $line, $matches)) {
				$name = $matches[0];
				$this->comment_block($script, $name, $comment);
				$comment = array();

			// class definition
			} elseif(preg_match('/^\s*class\s+(\w+)/i', $line, $matches)) {
				$name = $matches[0];
				$this->comment_block($script, $name, isset($comment)?$comment:'');
				$comment = array();

			// function definition
			} elseif(preg_match('/^\s*function\s+(&{0,1}\w+)\s*\((.*)\)/i', $line, $matches)) {
				$name = $matches[0];
				$this->comment_block($script, $name, isset($comment)?$comment:'');
				$comment = array();

			// only a comment
			} elseif(preg_match('/^\s*\/\//', $line)) {
				;

			// a blank line
			} elseif(preg_match('/^\s*$/', $line)) {
				;

			// not a declaration
			} elseif(@count($comment)) {
				$this->comment_block($script, '', $comment);
				$comment = array();
			}
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// generate the documentation page for this file
		$fields['name'] = $script;
		$fields['anchor'] = dirname($script);
		$fields['label'] = isset($this->index[$script]) ? $this->index[$script] : '*** you should add a phpDoc label line to this file';
		$fields['content'] = isset($this->comments[$script]) ? "[toc]".$this->comments[$script] : '*** you should expand phpDoc comments for this file';

		$query = "INSERT INTO ".SQL::table_name('phpdoc')." SET "
			." name='".SQL::escape($fields['name'])."',"
			." anchor='".SQL::escape($fields['anchor'])."',"
			." label='".SQL::escape($fields['label'])."',"
			." content='".SQL::escape($fields['content'])."',"
			." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		if(SQL::query($query, TRUE) === FALSE)
			echo $query.BR.SQL::error().BR."\n";
		$page_count++;

	}

	/**
	 * delete all documentation pages
	 */
	public static function purge() {
		global $context;

		// purge the old documentation
		$query = "DELETE FROM ".SQL::table_name('phpdoc');
		if((SQL::query($query, TRUE) === FALSE) && SQL::errno())
			echo $query.BR.SQL::error().BR."\n";
	}

	/**
	 * create a table for the php documentation
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['name'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['anchor']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['label']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['content']		= "MEDIUMTEXT NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(name)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX hits']		= "(hits)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['FULLTEXT INDEX']	= "full_text(name, content)";

		return SQL::setup_table('phpdoc', $fields, $indexes);
	}

}
?>
