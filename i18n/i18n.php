<?php
/**
 * handle internationalization
 *
 * @todo add a library of currencies
 *
 * This library helps to internationalize (i18n) and localize (l10n) strings used throughout the software.
 *
 * YACS leverages the gettext framework, which is the de facto standard for the internationalization of open source software.
 *
 * @link http://www.gnu.org/software/gettext/
 *
 * According to this framework, three kinds of files are used to achieve both internationalization and localization:
 *
 * [*] Machine Object - .mo files contain a binary representation of translated and localized strings.
 * These are the files that are loaded by YACS. You can either download .mo files containing official translations
 * from the YACS community, or translate some .pot files and compile them as .mo with a tool such as poEdit.
 * Localized files are split per language and per module. For example, all French strings for scripts in the directory articles
 * are loaded from the file i18n/locale/fr/articles.mo. Strings for root scripts are expected in the file i18n/locale/fr/root.mo.
 *
 * [*] Portable Object - .po files contain pairs of original and translated strings.
 * These are files handled and produced by software translators, or by any person with specific localization needs.
 * To create a new portable object, take a .pot template file and copy it as a .po file.
 * Then open the .po file with a dedicated tool such as poEdit to achieve the localization.
 *
 * [*] Portable Object Template - .pot files are collections of strings extracted from the YACS software.
 * These files are all located in the i18n/templates directory. They are generated as part of the release process of a new version of YACS.
 * Translators can use standard tools from the gettext toolbox to merge their previous .po files with new .pot versions.
 *
 *
 * Note that YACS does not actually use the gettext PHP extension, because it is not supported by a number of ISPs.
 * Instead, a small parser of .mo files has been developed, to allow smooth implementation across all servers.
 *
 * @link http://www.gnu.org/software/gettext/manual/html_chapter/gettext_8.html#SEC136 The Format of GNU MO Files
 *
 * [deprecated] In addition, YACS turns content of .mo files to standard PHP statements, in a .mo.php file.
 * This kind of file is searched first, since it is faster to include a PHP script than to parse a .mo file.
 *
 * [title]How to create a new localization?[/title]
 *
 * With each release of YACS a set of templates files (.pot) are generated in directory i18n/templates.
 * To create a new localization you have to:
 * - create a sub-directory in i18n/locale (see below)
 * - use the msginit command to create in the new directory a set of .po files out of .pot template files
 * - use poEdit, or any text editor, to populate .po files
 * - copy the manifest.php file from the 'en' locale, and adapt it for your new locale
 *
 * To name a new locale, you should follow best practices describes in RFC3066 and use any of following options:
 * - language code as defined by ISO 639; e.g., 'en' for English, 'ar' for Arabic, or 'zu' for Zulu
 * - language code, followed with an hyphen '-', then a country code as per ISO 3166-1, lower case; e.g., 'en-uk', 'zh-cn'
 *
 * @link http://www.ietf.org/rfc/rfc3066.txt Tags for Identification of Languages
 * @link http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes List of ISO 639-1 codes
 * @link http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2 ISO 3166-1 alpha-2
 *
 * The manifest file allows webmasters to manage localizations from YACS main configuration panel.
 *
 * [title]How to share and use localizations?[/title]
 *
 * Translators are encouraged to share the outcome of their work at the official YACS web site.
 * The preferred format for new submissions is an archive containing all .mo files, plus manifest.php, for one locale.
 *
 * At the moment YACS original archive includes full localization for English and French.
 * To add new localizations webmasters have to download additional sets of .mo files and to push them to servers.
 *
 * The preferred locale for a community can be selected from within YACS main configuration panel.
 *
 * Locales for surfers are automatically selected based on hints provided by web browsers, and on availability of related files.
 *
 *
 * @author Bernard Paques
 * @tester Alain Lesage (Lasares)
 * @tester Agnes
 * @tester Moi-meme
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class i18n {

	/**
	 * load localized strings for one module
	 *
	 * @param string module name
	 */
	function bind($module) {
		global $context;

		// sanity check
		if(!isset($context['language']))
			return;

		// initialization
		if(!isset($_SESSION['l10n_modules']))
			$_SESSION['l10n_modules'] = array();

		// ensure all cached modules are accurate on development machine
		if($context['with_debug'] == 'Y') {
			i18n::load('en', $module);
			i18n::load('fr', $module);
		}

		// this module has already been loaded
		if(isset($_SESSION['l10n_modules'][$module]))
			return;

		// avoid further loading
		$_SESSION['l10n_modules'][$module] = TRUE;

		// load strings according to surfer localization
		i18n::load($context['language'], $module);

		// load strings according to community localization
		if($context['preferred_language'] != $context['language'])
			i18n::load($context['preferred_language'], $module);

	}

	/**
	 * get a localized string based on preferred language for the community
	 *
	 * This function is an equivalent to gettext(), except it deals with community localization.
	 *
	 * @param string the template string to be translated
	 * @return string the localized string
	 */
	function &c($text) {
		global $context;

		// sanity check
		if(!$text)
			return $text;

		// select language used by community
		if(isset($context['preferred_language']))
			$locale = $context['preferred_language'];
		else
			$locale = 'en';

		// cache is empty
		if(!isset($_SESSION['l10n']) || !isset($_SESSION['l10n'][$locale]) || !is_array($_SESSION['l10n'][$locale]))
			return $text;

		// provide the localized string
		$text =& i18n::lookup($_SESSION['l10n'][$locale], $text);
		return $text;
	}

	/**
	 * filter text provided to surfer
	 *
	 * @see shared/codes.php
	 *
	 * @param string some text
	 * @param string the target language
	 * @return string the provided text, if surfer language matches the target language, else ''
	 */
	function &filter($text, $language) {
		global $context;

		// sanity check
		if(!isset($context['language']))
			return $text;

		// surfer is using a different language
		if($language != $context['language'])
			$text = '';

		// job done
		return $text;
	}

	/**
	 * the database of country codes
	 *
	 * @link http://www.videoscash.info/countrycodes.html Mod GeoIP country codes
	 *
	 * @return array of ($label => code)
	 */
	function &get_countries() {

		// initialize the table only once
		static $codes;
		if(!is_array($codes)) {

			// alphabetical order of countries (not of codes)
			$codes = array(
				'AF'	=> i18n::s('Afghanistan'),
				'AL'	=> i18n::s('Albania'),
				'DZ'	=> i18n::s('Algeria'),
				'AS'	=> i18n::s('American Samoa'),
				'AD'	=> i18n::s('Andorra'),
				'AO'	=> i18n::s('Angola'),
				'AI'	=> i18n::s('Anguilla'),
				'A1'	=> i18n::s('Anonymous Proxy'),
				'AQ'	=> i18n::s('Antarctica'),
				'AG'	=> i18n::s('Antigua and Barbuda'),
				'AR'	=> i18n::s('Argentina'),
				'AM'	=> i18n::s('Armenia'),
				'AW'	=> i18n::s('Aruba'),
				'AP'	=> i18n::s('Asia/Pacific'),	// GeoIP
				'AU'	=> i18n::s('Australia'),
				'AT'	=> i18n::s('Austria'),
				'AZ'	=> i18n::s('Azerbaijan'),
				'BS'	=> i18n::s('Bahamas'),
				'BH'	=> i18n::s('Bahrain'),
				'BD'	=> i18n::s('Bangladesh'),
				'BB'	=> i18n::s('Barbados'),
				'BY'	=> i18n::s('Belarus'),
				'BE'	=> i18n::s('Belgium'),
				'BZ'	=> i18n::s('Belize'),
				'BJ'	=> i18n::s('Benin'),
				'BM'	=> i18n::s('Bermuda'),
				'BT'	=> i18n::s('Bhutan'),
				'BO'	=> i18n::s('Bolivia'),
				'BA'	=> i18n::s('Bosnia and Herzegovina'),
				'BW'	=> i18n::s('Botswana'),
				'BV'	=> i18n::s('Bouvet Island'),
				'BR'	=> i18n::s('Brazil'),
				'IO'	=> i18n::s('British Indian Ocean Territory'),
				'BN'	=> i18n::s('Brunei Darussalam'),
				'BG'	=> i18n::s('Bulgaria'),
				'BF'	=> i18n::s('Burkina Faso'),
				'BI'	=> i18n::s('Burundi'),
				'KH'	=> i18n::s('Cambodia'),
				'CM'	=> i18n::s('Cameroon'),
				'CA'	=> i18n::s('Canada'),
				'CV'	=> i18n::s('Cape Verde'),
				'KY'	=> i18n::s('Cayman Islands'),
				'CF'	=> i18n::s('Central African Republic'),
				'TD'	=> i18n::s('Chad'),
				'CL'	=> i18n::s('Chile'),
				'CN'	=> i18n::s('China'),
				'CX'	=> i18n::s('Christmas Island'),
				'CC'	=> i18n::s('Cocos (keeling) Islands'),
				'CO'	=> i18n::s('Colombia'),
				'KM'	=> i18n::s('Comoros'),
				'CG'	=> i18n::s('Congo'),
				'CD'	=> i18n::s('Congo, the Democratic Republic of the'),
				'CK'	=> i18n::s('Cook Islands '),
				'CR'	=> i18n::s('Costa Rica'),
				'CI'	=> i18n::s('Cote D\'ivoire'),
				'HR'	=> i18n::s('Croatia'),
				'CU'	=> i18n::s('Cuba'),
				'CY'	=> i18n::s('Cyprus'),
				'CZ'	=> i18n::s('Czech Republic'),
				'DK'	=> i18n::s('Denmark'),
				'DJ'	=> i18n::s('Djibouti'),
				'DM'	=> i18n::s('Dominica'),
				'DO'	=> i18n::s('Dominican Republic'),
				'TL'	=> i18n::s('East Timor'),	// GeoIP
				'TP'	=> i18n::s('East Timor'),	// ISO
				'EC'	=> i18n::s('Ecuador'),
				'EG'	=> i18n::s('Egypt'),
				'EU'	=> i18n::s('Europe'),	// GeoIP
				'SV'	=> i18n::s('El Salvador'),
				'GQ'	=> i18n::s('Equatorial Guinea'),
				'ER'	=> i18n::s('Eritrea'),
				'EE'	=> i18n::s('Estonia'),
				'ET'	=> i18n::s('Ethiopia'),
				'FK'	=> i18n::s('Falkland Islands (malvinas)'),
				'FO'	=> i18n::s('Faroe Islands'),
				'FJ'	=> i18n::s('Fiji'),
				'FI'	=> i18n::s('Finland'),
				'FR'	=> i18n::s('France'),
				'FX'	=> i18n::s('France, Metropolitan'),	// GeoIP
				'GF'	=> i18n::s('French Guiana'),
				'PF'	=> i18n::s('French Polynesia'),
				'TF'	=> i18n::s('French Southern Territories'),
				'GA'	=> i18n::s('Gabon'),
				'GM'	=> i18n::s('Gambia'),
				'GE'	=> i18n::s('Georgia'),
				'DE'	=> i18n::s('Germany'),
				'GH'	=> i18n::s('Ghana'),
				'GI'	=> i18n::s('Gibraltar'),
				'GR'	=> i18n::s('Greece'),
				'GL'	=> i18n::s('Greenland'),
				'GD'	=> i18n::s('Grenada'),
				'GP'	=> i18n::s('Guadeloupe'),
				'GU'	=> i18n::s('Guam'),
				'GT'	=> i18n::s('Guatemala'),
				'GN'	=> i18n::s('Guinea'),
				'GW'	=> i18n::s('Guinea-bissau'),
				'GY'	=> i18n::s('Guyana'),
				'HT'	=> i18n::s('Haiti'),
				'HM'	=> i18n::s('Heard Island and Mcdonald Islands'),
				'VA'	=> i18n::s('Holy See (vatican City State)'),
				'HN'	=> i18n::s('Honduras'),
				'HK'	=> i18n::s('Hong Kong'),
				'HU'	=> i18n::s('Hungary'),
				'IS'	=> i18n::s('Iceland'),
				'IN'	=> i18n::s('India'),
				'ID'	=> i18n::s('Indonesia'),
				'IR'	=> i18n::s('Iran, Islamic Republic of'),
				'IQ'	=> i18n::s('Iraq'),
				'IE'	=> i18n::s('Ireland'),
				'IL'	=> i18n::s('Israel'),
				'IT'	=> i18n::s('Italy'),
				'JM'	=> i18n::s('Jamaica'),
				'JP'	=> i18n::s('Japan'),
				'JO'	=> i18n::s('Jordan'),
				'KZ'	=> i18n::s('Kazakstan'),
				'KE'	=> i18n::s('Kenya'),
				'KI'	=> i18n::s('Kiribati'),
				'KP'	=> i18n::s('Korea, Democratic People\'s Republic of'),
				'KR'	=> i18n::s('Korea, Republic of'),
				'KW'	=> i18n::s('Kuwait'),
				'KG'	=> i18n::s('Kyrgyzstan'),
				'LA'	=> i18n::s('Lao People\'s Democratic Republic'),
				'LV'	=> i18n::s('Latvia'),
				'LB'	=> i18n::s('Lebanon'),
				'LS'	=> i18n::s('Lesotho'),
				'LR'	=> i18n::s('Liberia'),
				'LY'	=> i18n::s('Libyan Arab Jamahiriya'),
				'LI'	=> i18n::s('Liechtenstein'),
				'LT'	=> i18n::s('Lithuania'),
				'LU'	=> i18n::s('Luxembourg'),
				'MO'	=> i18n::s('Macau'),
				'MK'	=> i18n::s('Macedonia, the Former Yugoslav Republic of'),
				'MG'	=> i18n::s('Madagascar'),
				'MW'	=> i18n::s('Malawi'),
				'MY'	=> i18n::s('Malaysia'),
				'MV'	=> i18n::s('Maldives'),
				'ML'	=> i18n::s('Mali'),
				'MT'	=> i18n::s('Malta'),
				'MH'	=> i18n::s('Marshall Islands'),
				'MQ'	=> i18n::s('Martinique'),
				'MR'	=> i18n::s('Mauritania'),
				'MU'	=> i18n::s('Mauritius'),
				'YT'	=> i18n::s('Mayotte'),
				'MX'	=> i18n::s('Mexico'),
				'FM'	=> i18n::s('Micronesia, Federated States of'),
				'MD'	=> i18n::s('Moldova, Republic of'),
				'MC'	=> i18n::s('Monaco'),
				'MN'	=> i18n::s('Mongolia'),
				'ME'	=> i18n::s('Montenegro'),
				'MS'	=> i18n::s('Montserrat'),
				'MA'	=> i18n::s('Morocco'),
				'MZ'	=> i18n::s('Mozambique'),
				'MM'	=> i18n::s('Myanmar'),
				'NA'	=> i18n::s('Namibia'),
				'NR'	=> i18n::s('Nauru'),
				'NP'	=> i18n::s('Nepal'),
				'NL'	=> i18n::s('Netherlands'),
				'AN'	=> i18n::s('Netherlands Antilles'),
				'NC'	=> i18n::s('New Caledonia'),
				'NZ'	=> i18n::s('New Zealand'),
				'NI'	=> i18n::s('Nicaragua'),
				'NE'	=> i18n::s('Niger'),
				'NG'	=> i18n::s('Nigeria'),
				'NU'	=> i18n::s('Niue'),
				'NF'	=> i18n::s('Norfolk Island'),
				'MP'	=> i18n::s('Northern Mariana Islands'),
				'NO'	=> i18n::s('Norway'),
				'OM'	=> i18n::s('Oman'),
				'PK'	=> i18n::s('Pakistan'),
				'PW'	=> i18n::s('Palau'),
				'PS'	=> i18n::s('Palestinian Territory, Occupied'),
				'PA'	=> i18n::s('Panama'),
				'PG'	=> i18n::s('Papua New Guinea'),
				'PY'	=> i18n::s('Paraguay'),
				'PE'	=> i18n::s('Peru'),
				'PH'	=> i18n::s('Philippines'),
				'PN'	=> i18n::s('Pitcairn'),
				'PL'	=> i18n::s('Poland'),
				'PT'	=> i18n::s('Portugal'),
				'PR'	=> i18n::s('Puerto Rico'),
				'QA'	=> i18n::s('Qatar'),
				'RE'	=> i18n::s('Reunion'),
				'RO'	=> i18n::s('Romania'),
				'RU'	=> i18n::s('Russian Federation'),
				'RW'	=> i18n::s('Rwanda'),
				'SH'	=> i18n::s('Saint Helena'),
				'KN'	=> i18n::s('Saint Kitts and Nevis'),
				'LC'	=> i18n::s('Saint Lucia'),
				'PM'	=> i18n::s('Saint Pierre and Miquelon'),
				'VC'	=> i18n::s('Saint Vincent and the Grenadines'),
				'WS'	=> i18n::s('Samoa'),
				'SM'	=> i18n::s('San Marino'),
				'ST'	=> i18n::s('Sao Tome and Principe'),
				'A2'	=> i18n::s('Satellite Provider'),
				'SA'	=> i18n::s('Saudi Arabia'),
				'SN'	=> i18n::s('Senegal'),
				'RS'	=> i18n::s('Serbia'),
				'SC'	=> i18n::s('Seychelles'),
				'SL'	=> i18n::s('Sierra Leone'),
				'SG'	=> i18n::s('Singapore'),
				'SK'	=> i18n::s('Slovakia'),
				'SI'	=> i18n::s('Slovenia'),
				'SB'	=> i18n::s('Solomon Islands'),
				'SO'	=> i18n::s('Somalia'),
				'ZA'	=> i18n::s('South Africa'),
				'GS'	=> i18n::s('South Georgia and the South Sandwich Islands'),
				'ES'	=> i18n::s('Spain'),
				'LK'	=> i18n::s('Sri Lanka'),
				'SD'	=> i18n::s('Sudan'),
				'SR'	=> i18n::s('Suriname'),
				'SJ'	=> i18n::s('Svalbard and Jan Mayen'),
				'SZ'	=> i18n::s('Swaziland'),
				'SE'	=> i18n::s('Sweden'),
				'CH'	=> i18n::s('Switzerland'),
				'SY'	=> i18n::s('Syrian Arab Republic'),
				'TW'	=> i18n::s('Taiwan, Province of China'),
				'TJ'	=> i18n::s('Tajikistan'),
				'TZ'	=> i18n::s('Tanzania, United Republic of'),
				'TH'	=> i18n::s('Thailand'),
				'TG'	=> i18n::s('Togo'),
				'TK'	=> i18n::s('Tokelau'),
				'TO'	=> i18n::s('Tonga'),
				'TT'	=> i18n::s('Trinidad and Tobago'),
				'TN'	=> i18n::s('Tunisia'),
				'TR'	=> i18n::s('Turkey'),
				'TM'	=> i18n::s('Turkmenistan'),
				'TC'	=> i18n::s('Turks and Caicos Islands'),
				'TV'	=> i18n::s('Tuvalu'),
				'UG'	=> i18n::s('Uganda'),
				'UA'	=> i18n::s('Ukraine'),
				'AE'	=> i18n::s('United Arab Emirates'),
				'GB'	=> i18n::s('United Kingdom'),
				'US'	=> i18n::s('United States'),
				'UM'	=> i18n::s('United States Minor Outlying Islands'),
				'UY'	=> i18n::s('Uruguay'),
				'UZ'	=> i18n::s('Uzbekistan'),
				'VU'	=> i18n::s('Vanuatu'),
				'VE'	=> i18n::s('Venezuela'),
				'VN'	=> i18n::s('Viet Nam'),
				'VG'	=> i18n::s('Virgin Islands, British'),
				'VI'	=> i18n::s('Virgin Islands, U.s.'),
				'WF'	=> i18n::s('Wallis and Futuna'),
				'EH'	=> i18n::s('Western Sahara'),
				'YE'	=> i18n::s('Yemen'),
				'ZM'	=> i18n::s('Zambia'),
				'ZR'	=> i18n::s('Zaire'),	// GeoIP
				'ZW'	=> i18n::s('Zimbabwe')
			);

		}

		// return the table
		return $codes;
	}

	/**
	 * get country codes as options of a selectable list
	 *
	 * This function returns a full &lt;select&gt; tag with the name 'country' and the id 'country'.
	 *
	 * @param string the current country, if any
	 * @param string alternate name and id for the returned tag
	 * @return the HTML to insert in the page
	 */
	function &get_countries_select($current=NULL, $id='country') {
		global $context;

		// all options
		$text = '<select name="'.$id.'" id="'.$id.'">'."\n";

		// fetch the list of countries
		$countries =& i18n::get_countries();

		// engage surfer
		if(!$current)
			$text .= '<option>'.i18n::s('Select a country')."</option>\n";

		// all options
		foreach($countries as $code => $label) {

			// the code
			$text .= '<option value="'.$code.'"';

			// is this the current option?
			if(strpos($current, $code) === 0)
				$text .= ' selected="selected"';

			// the label for this code
			$text .= '>'.$label."</option>\n";

		}

		// return by reference
		$text .= '</select>';

		// job done
		return $text;
	}

	/**
	 * get country label
	 *
	 * This function uses labels in surfer language, and not in community language.
	 *
	 * @param string the country code
	 * @return string the related label, or NULL if the code is unknown
	 */
	function get_country_label($code='') {
		global $context;

		// sanity check
		if(strlen($code) < 2)
			return NULL;

		// fetch the list of countries
		$countries =& i18n::get_countries();

		// translate the code
		if(isset($countries[$code]))
			return $countries[$code];

		// country is unknown
		return NULL;
	}

	/**
	 * get language label
	 *
	 * This function uses labels in surfer language, and not in community language.
	 *
	 * @param string the language code
	 * @return string the related label, or NULL if the code is unknown
	 */
	function get_language_label($code='') {
		global $context;

		// sanity check
		if(strlen($code) < 2)
			return NULL;

		// fetch the list of languages
		$languages =& i18n::get_languages();

		// translate the code
		if(isset($languages[$code]))
			return $languages[$code];

		// language is unknown
		return NULL;
	}

	/**
	 * the database of language codes
	 *
	 * The full set of ISO639 2-letter codes, with their localized name.
	 *
	 * @link http://www.oasis-open.org/cover/iso639a.html ISO 639, revised 1989
	 *
	 * @return array of ($label => code)
	 */
	function &get_languages() {

		// initialize the table only once
		static $codes;
		if(!is_array($codes)) {

			// alphabetical order of languages (not of codes)
			$codes = array(
				'ab'	=> i18n::s('abkhazian'),
				'om'	=> i18n::s('afan (oromo)'),
				'aa'	=> i18n::s('afar'),
				'af'	=> i18n::s('afrikaans'),
				'sq'	=> i18n::s('albanian'),
				'am'	=> i18n::s('amharic'),
				'ar'	=> i18n::s('arabic'),
				'hy'	=> i18n::s('armenian'),
				'as'	=> i18n::s('assamese'),
				'ay'	=> i18n::s('aymara'),
				'az'	=> i18n::s('azerbaijani'),
				'ba'	=> i18n::s('bashkir'),
				'eu'	=> i18n::s('basque'),
				'bn'	=> i18n::s('bengali;bangla'),
				'bh'	=> i18n::s('bihari'),
				'bi'	=> i18n::s('bislama'),
				'br'	=> i18n::s('breton'),
				'bg'	=> i18n::s('bulgarian'),
				'my'	=> i18n::s('burmese'),
				'be'	=> i18n::s('byelorussian'),
				'ca'	=> i18n::s('catalan'),
				'zh'	=> i18n::s('chinese'),
				'co'	=> i18n::s('corsican'),
				'hr'	=> i18n::s('croatian'),
				'cs'	=> i18n::s('czech'),
				'da'	=> i18n::s('danish'),
				'nl'	=> i18n::s('dutch'),
				'dz'	=> i18n::s('dzongkha'),
				'en'	=> i18n::s('english'),
				'eo'	=> i18n::s('esperanto'),
				'et'	=> i18n::s('estonian'),
				'fo'	=> i18n::s('faroese'),
				'fj'	=> i18n::s('fiji'),
				'fi'	=> i18n::s('finnish'),
				'fr'	=> i18n::s('french'),
				'fy'	=> i18n::s('frisian'),
				'gl'	=> i18n::s('galician'),
				'ka'	=> i18n::s('georgian'),
				'de'	=> i18n::s('german'),
				'el'	=> i18n::s('greek'),
				'kl'	=> i18n::s('greenlandic'),
				'gn'	=> i18n::s('guarani'),
				'gu'	=> i18n::s('gujarati'),
				'ha'	=> i18n::s('hausa'),
				'he'	=> i18n::s('hebrew'),
				'hi'	=> i18n::s('hindi'),
				'hu'	=> i18n::s('hungarian'),
				'is'	=> i18n::s('icelandic'),
				'id'	=> i18n::s('indonesian'),
				'ia'	=> i18n::s('interlingua'),
				'ie'	=> i18n::s('interlingue'),
				'iu'	=> i18n::s('inuktitut'),
				'ik'	=> i18n::s('inupiak'),
				'ga'	=> i18n::s('irish'),
				'it'	=> i18n::s('italian'),
				'ja'	=> i18n::s('japanese'),
				'jv'	=> i18n::s('javanese'),
				'kn'	=> i18n::s('kannada'),
				'ks'	=> i18n::s('kashmiri'),
				'kk'	=> i18n::s('kazakh'),
				'km'	=> i18n::s('khmer'),
				'rw'	=> i18n::s('kinyarwanda'),
				'ky'	=> i18n::s('kirghiz'),
				'ko'	=> i18n::s('korean'),
				'ku'	=> i18n::s('kurdish'),
				'rn'	=> i18n::s('kurundi'),
				'lo'	=> i18n::s('laothian'),
				'la'	=> i18n::s('latin'),
				'lv'	=> i18n::s('latvian;lettish'),
				'ln'	=> i18n::s('lingala'),
				'lt'	=> i18n::s('lithuanian'),
				'mk'	=> i18n::s('macedonian'),
				'mg'	=> i18n::s('malagasy'),
				'ms'	=> i18n::s('malay'),
				'ml'	=> i18n::s('malayalam'),
				'mt'	=> i18n::s('maltese'),
				'mi'	=> i18n::s('maori'),
				'mr'	=> i18n::s('marathi'),
				'mo'	=> i18n::s('moldavian'),
				'mn'	=> i18n::s('mongolian'),
				'na'	=> i18n::s('nauru'),
				'ne'	=> i18n::s('nepali'),
				'no'	=> i18n::s('norwegian'),
				'oc'	=> i18n::s('occitan'),
				'or'	=> i18n::s('oriya'),
				'ps'	=> i18n::s('pashto;pushto'),
				'fa'	=> i18n::s('persian (farsi)'),
				'pl'	=> i18n::s('polish'),
				'pt'	=> i18n::s('portuguese'),
				'pa'	=> i18n::s('punjabi'),
				'qu'	=> i18n::s('quechua'),
				'rm'	=> i18n::s('rhaeto-romance'),
				'ro'	=> i18n::s('romanian'),
				'ru'	=> i18n::s('russian'),
				'sm'	=> i18n::s('samoan'),
				'sg'	=> i18n::s('sangho'),
				'sa'	=> i18n::s('sanskrit'),
				'gd'	=> i18n::s('scots gaelic'),
				'sr'	=> i18n::s('serbian'),
				'sh'	=> i18n::s('serbo-croatian'),
				'st'	=> i18n::s('sesotho'),
				'tn'	=> i18n::s('setswana'),
				'sn'	=> i18n::s('shona'),
				'sd'	=> i18n::s('sindhi'),
				'si'	=> i18n::s('singhalese'),
				'ss'	=> i18n::s('siswati'),
				'sk'	=> i18n::s('slovak'),
				'sl'	=> i18n::s('slovenian'),
				'so'	=> i18n::s('somali'),
				'es'	=> i18n::s('spanish'),
				'su'	=> i18n::s('sundanese'),
				'sw'	=> i18n::s('swahili'),
				'sv'	=> i18n::s('swedish'),
				'tl'	=> i18n::s('tagalog'),
				'tg'	=> i18n::s('tajik'),
				'ta'	=> i18n::s('tamil'),
				'tt'	=> i18n::s('tatar'),
				'te'	=> i18n::s('telugu'),
				'th'	=> i18n::s('thai'),
				'bo'	=> i18n::s('tibetan'),
				'ti'	=> i18n::s('tigrinya'),
				'to'	=> i18n::s('tonga'),
				'ts'	=> i18n::s('tsonga'),
				'tr'	=> i18n::s('turkish'),
				'tk'	=> i18n::s('turkmen'),
				'tw'	=> i18n::s('twi'),
				'ug'	=> i18n::s('uigur'),
				'uk'	=> i18n::s('ukrainian'),
				'ur'	=> i18n::s('urdu'),
				'uz'	=> i18n::s('uzbek'),
				'vi'	=> i18n::s('vietnamese'),
				'vo'	=> i18n::s('volapuk'),
				'cy'	=> i18n::s('welsh'),
				'wo'	=> i18n::s('wolof'),
				'xh'	=> i18n::s('xhosa'),
				'yi'	=> i18n::s('yiddish'),
				'yo'	=> i18n::s('yoruba'),
				'za'	=> i18n::s('zhuang'),
				'zu'	=> i18n::s('zulu')
			);

		}

		// return the table
		return $codes;
	}

	/**
	 * get language codes as options of a selectable list
	 *
	 * This function returns a full &lt;select&gt; tag with the name 'language' and the id 'language'.
	 *
	 * If no parameter is provided, then surfer language is used.
	 *
	 * @param string the current language, if any
	 * @param string alternate name and id of the returned tag
	 * @return the HTML to insert in the page
	 */
	function &get_languages_select($current=NULL, $id='language') {
		global $context;

		// use surfer language by default
		if(!$current)
			$current = $context['language'];

		// all options
		$text = '<select name="'.$id.'" id="'.$id.'">'."\n";

		// fetch the list of languages
		$languages =& i18n::get_languages();

		// engage surfer
		$text .= '<option value="none">'.i18n::s('Select a language')."</option>\n";

		// current language setting
		if($current == $context['language'])
			$text .= '<option value="'.$context['language'].'" selected="selected">'.i18n::get_language_label($context['language'])."</option>\n";
		else
			$text .= '<option value="'.$context['language'].'">'.i18n::get_language_label($context['language'])."</option>\n";

		// all options
		foreach($languages as $code => $label) {

			// is this the current setting?
			if($code == $context['language'])
				continue;

			// the code
			$text .= '<option value="'.$code.'"';

			// is this the current option?
			if($code == $current)
				$text .= ' selected="selected"';

			// the label for this language
			$text .= '>'.$label."</option>\n";

		}

		// return by reference
		$text .= '</select>';

		// job done
		return $text;
	}

	/**
	 * provide a localized template
	 *
	 * @param string type of the expected template
	 * @return string text of the template
	 */
	function &get_template($id) {

		// depending of the expected template
		switch($id) {

		case 'mail_notification':
		default:

			// action, then title and link
			$text = "%s\n\n%s\n%s";
			break;

		}

		// job done
		return $text;
	}

	/**
	 * the database of time zones
	 *
	 * @return array of ($shift => $label)
	 */
	function &get_time_zones() {

		// initialize the table only once
		static $codes;
		if(!is_array($codes)) {

			// alphabetical order of countries (not of codes)
			$codes = array(
				'UTC' => '(UTC) Casablanca, Monrovia',
				'UTC' => '(UTC) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London',
				'UTC+01:00' => '(UTC+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
				'UTC+01:00' => '(UTC+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
				'UTC+01:00' => '(UTC+01:00) Brussels, Copenhagen, Madrid, Paris',
				'UTC+01:00' => '(UTC+01:00) Sarajevo, Skopje, Sofija, Vilnius, Warsaw, Zagreb',
				'UTC+01:00' => '(UTC+01:00) West Central Africa',
				'UTC+02:00' => '(UTC+02:00) Athens, Istanbul, Minsk',
				'UTC+02:00' => '(UTC+02:00) Bucharest',
				'UTC+02:00' => '(UTC+02:00) Cairo',
				'UTC+02:00' => '(UTC+02:00) Harare, Pretoria',
				'UTC+02:00' => '(UTC+02:00) Helsinki, Riga, Tallinn',
				'UTC+02:00' => '(UTC+02:00) Jerusalem',
				'UTC+03:00' => '(UTC+03:00) Baghdad',
				'UTC+03:00' => '(UTC+03:00) Kuwait, Riyadh',
				'UTC+03:00' => '(UTC+03:00) Moscow, St. Petersburg, Volgograd',
				'UTC+03:00' => '(UTC+03:00) Nairobi',
				'UTC+03:30' => '(UTC+03:30) Tehran',
				'UTC+04:00' => '(UTC+04:00) Abu Dhabi, Muscat',
				'UTC+04:00' => '(UTC+04:00) Baku, Tbilisi, Yerevan',
				'UTC+04:30' => '(UTC+04:30) Kabul',
				'UTC+05:00' => '(UTC+05:00) Ekaterinburg',
				'UTC+05:00' => '(UTC+05:00) Islamabad, Karachi, Tashkent',
				'UTC+05:30' => '(UTC+05:30) Calcutta, Chennai, Mumbai, New Delhi',
				'UTC+05:45' => '(UTC+05:45) Kathmandu',
				'UTC+06:00' => '(UTC+06:00) Almaty, Novosibirsk',
				'UTC+06:00' => '(UTC+06:00) Astana, Dhaka',
				'UTC+06:00' => '(UTC+06:00) Sri Jayawardenepura',
				'UTC+06:30' => '(UTC+06:30) Rangoon',
				'UTC+07:00' => '(UTC+07:00) Bangkok, Hanoi, Jakarta',
				'UTC+07:00' => '(UTC+07:00) Krasnoyarsk',
				'UTC+08:00' => '(UTC+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
				'UTC+08:00' => '(UTC+08:00) Irkutsk, Ulaan Bataar',
				'UTC+08:00' => '(UTC+08:00) Kuala Lumpur, Singapore',
				'UTC+08:00' => '(UTC+08:00) Perth',
				'UTC+08:00' => '(UTC+08:00) Taipei',
				'UTC+09:00' => '(UTC+09:00) Osaka, Sapporo, Tokyo',
				'UTC+09:00' => '(UTC+09:00) Seoul',
				'UTC+09:00' => '(UTC+09:00) Yakutsk',
				'UTC+09:30' => '(UTC+09:30) Adelaide',
				'UTC+09:30' => '(UTC+09:30) Darwin',
				'UTC+10:00' => '(UTC+10:00) Brisbane',
				'UTC+10:00' => '(UTC+10:00) Canberra, Melbourne, Sydney',
				'UTC+10:00' => '(UTC+10:00) Guam, Port Moresby',
				'UTC+10:00' => '(UTC+10:00) Hobart',
				'UTC+10:00' => '(UTC+10:00) Vladivostok',
				'UTC+11:00' => '(UTC+11:00) Magadan, Solomon Is., New Caledonia',
				'UTC+12:00' => '(UTC+12:00) Auckland, Wellington',
				'UTC+12:00' => '(UTC+12:00) Fiji, Kamchatka, Marshall Is.',
				'UTC+13:00' => '(UTC+13:00) Nuku&#39;alofa',
				'UTC-01:00' => '(UTC-01:00) Azores',
				'UTC-01:00' => '(UTC-01:00) Cape Verde Is.',
				'UTC-02:00' => '(UTC-02:00) Mid-Atlantic',
				'UTC-03:00' => '(UTC-03:00) Brasilia',
				'UTC-03:00' => '(UTC-03:00) Buenos Aires, Georgetown',
				'UTC-03:00' => '(UTC-03:00) Greenland',
				'UTC-03:30' => '(UTC-03:30) Newfoundland',
				'UTC-04:00' => '(UTC-04:00) Atlantic Time (Canada)',
				'UTC-04:00' => '(UTC-04:00) Caracas, La Paz',
				'UTC-04:00' => '(UTC-04:00) Santiago',
				'UTC-05:00' => '(UTC-05:00) Bogota, Lima, Quito',
				'UTC-05:00' => '(UTC-05:00) Eastern Time (US &amp; Canada)',
				'UTC-05:00' => '(UTC-05:00) Indiana (East)',
				'UTC-06:00' => '(UTC-06:00) Central America',
				'UTC-06:00' => '(UTC-06:00) Central Time (US &amp; Canada)',
				'UTC-06:00' => '(UTC-06:00) Mexico City',
				'UTC-06:00' => '(UTC-06:00) Saskatchewan',
				'UTC-07:00' => '(UTC-07:00) Arizona',
				'UTC-07:00' => '(UTC-07:00) Mountain Time (US &amp; Canada)',
				'UTC-08:00' => '(UTC-08:00) Pacific Time (US &amp; Canada); Tijuana',
				'UTC-09:00' => '(UTC-09:00) Alaska',
				'UTC-10:00' => '(UTC-10:00) Hawaii',
				'UTC-11:00' => '(UTC-11:00) Midway Island, Samoa',
				'UTC-12:00' => '(UTC-12:00) Eniwetok, Kwajalein'
			);

		}

		// return the table
		return $codes;
	}

	/**
	 * hash a string
	 *
	 * @param string original string
	 * @return string hashed string
	 */
	function &hash($text) {

		if(strlen($text) < 32)
			$output = $text;
		else
			$output = md5($text);

		return $output;
	}

	/**
	 * initialize the localization engine
	 *
	 * This function analyzes data provided by the browser to automate surfer localization.
	 *
	 */
	function initialize() {
		global $context;

		// user language is explicit
		if(isset($_SESSION['surfer_language']) && $_SESSION['surfer_language'] && ($_SESSION['surfer_language'] != 'none'))
			$context['language'] = $_SESSION['surfer_language'];

		// guess surfer language
		else {

			// languages accepted by browser
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'])
				$accepted = explode(',', str_replace('en-securid,', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
			else
				$accepted = array('*');

			// score each language
			$scores = array();
			foreach($accepted as $item) {
				$parts = explode(';', trim($item));
				if(isset($parts[1]))
					$scores[$parts[0]] = (float)trim($parts[1], ' q=');
				else
					$scores[$parts[0]] = (float)1.0;
			}

			// process each wish in sequence --most preferred language comes first without sorting data
			foreach($scores as $locale => $score) {

				// ensure full locale availability
				$path = 'i18n/locale/'.$locale.'/i18n.mo';
				if(file_exists($context['path_to_root'].$path) || file_exists($context['path_to_root'].$path.'.php')) {

						// this is guessed surfer locale
						$context['language'] = $locale;

						// drop other accepted languages
						break;

				}

				// locale has no country code
				if(!$position = strpos($locale, '-'))
					continue;

				// check for availability of basic language file
				$locale = substr($locale, 0, $position);
				$path = 'i18n/locale/'.$locale.'/i18n.mo';
				if(file_exists($context['path_to_root'].$path) || file_exists($context['path_to_root'].$path.'.php')) {

						// this is guessed surfer locale
						$context['language'] = $locale;

						// drop other accepted languages
						break;

				}
			}
		}

		// set community language
		if(isset($context['preferred_language']))
			;

		// use surfer guessed language, if any
		elseif(isset($context['language']) && $context['language'])
			$context['preferred_language'] = $context['language'];

		// english is the default
		else
			$context['preferred_language'] = 'en';

		// automatic detection has been disallowed
		if(isset($context['without_language_detection']) && ($context['without_language_detection'] == 'Y'))
			$context['language'] = $context['preferred_language'];

		// english is the default
		elseif(!isset($context['language']))
			$context['language'] = 'en';

		// set the country, if known
		if(isset($_SERVER['GEOIP_COUNTRY_CODE'])) {
			$context['country_code'] = $_SERVER['GEOIP_COUNTRY_CODE'];
			$context['country'] = i18n::get_country_label($_SERVER['GEOIP_COUNTRY_CODE']);
		}

	}

	/**
	 * lookup a localised string in an array
	 *
	 * This can be used to parse manifest files for example.
	 *
	 * This function also transcode HTML entities to Unicode entities, if any.
	 *
	 * @param array the array containing localized strings
	 * @param string the label identifying string
	 * @param string desired language, if any
	 * @return string the localized string, if any
	 */
	function &l($strings, $name, $forced='') {
		global $context;

		// sanity check
		if(!$name)
			return $name;

		// select a string
		if($forced && ($key = $name.'_'.$forced) && array_key_exists($key, $strings))
			$text = $strings[ $key ];
		elseif(($key = $name.'_'.$context['language']) && array_key_exists($key, $strings))
			$text = $strings[ $key ];
		elseif(($key = $name.'_en') && array_key_exists($key, $strings))
			$text = $strings[ $key ];
		elseif(array_key_exists($name, $strings))
			$text = $strings[ $name ];
		else {
			$text = $name;
			if($context['with_debug'] == 'Y')
				logger::remember('i18n/i18n.php', $name.' is not localized', '', 'debug');

		}

		// the file may be absent during updates
		Safe::load('shared/utf8.php');

		// transcode to utf8
		if(isset($context['charset']) && ($context['charset'] == 'utf-8') && is_callable(array('utf8', 'transcode')))
			$text =& utf8::transcode($text);

		return $text;
	}

	/**
	 * look for a localized string
	 *
	 * @param array the array containing localized strings
	 * @param string the label identifying string
	 * @return string the localized string, if any
	 */
	function &lookup($strings, $name) {
		global $context;

		// match on hashed name
		if(($hash = i18n::hash($name)) && array_key_exists($hash, $strings))
			$text = $strings[ $hash ];

		// no match
		else {

			// log information on development platform
			if(($context['with_debug'] == 'Y') && file_exists($context['path_to_root'].'parameters/switch.on'))
				logger::remember('i18n/i18n.php', $name.' is not localized', '', 'debug');

			// degrade to provided string
			$text = $name;
		}

		// provide the localized string
		return $text;
	}

	/**
	 * load one .mo file
	 *
	 * This function attempts to include a cached version (actually, a .mo.php
	 * file), then, if it is not available, it parses the .mo file.
	 *
	 * Expected data locations for language 'foo' and module 'bar' are:
	 * - i18n/locale/foo/bar.mo.php (for the PHP equivalent to the .mo file) and
	 * - i18n/locale/foo/bar.mo (for the original .mo file)
	 *
	 * The function also attempts to create a cached PHP version of the file
	 * if one does not exists, to speed up subsequent calls.
	 *
	 * Loaded strings are all placed into the global array $_SESSION['l10n'] for later use.
	 *
	 * The function does not actually use the gettext PHP extension because of potential weak implementations.
	 * Instead, it parses directly binary content of the .mo file.
	 *
	 * @link http://www.gnu.org/software/gettext/manual/html_chapter/gettext_8.html#SEC136 The Format of GNU MO Files
	 *
	 * @param string target language
	 * @param string target module
	 * @return TRUE on success, FALSE otherwise
	 */
	function load($language, $module) {
		global $context;

		// sanity check
		if(!$language)
			return FALSE;

		// expected location
		$path = 'i18n/locale/'.$language.'/'.$module.'.mo';

		// translations have a global scope
		if(!isset($_SESSION['l10n']))
			$_SESSION['l10n'] = array();
		if(!isset($_SESSION['l10n'][$language]))
			$_SESSION['l10n'][$language] = array();

		// load PHP version, if it exists, and if it is fresher than the original
		$hash = $context['path_to_root'].$path.'.php';
		if(is_readable($hash) && is_readable($context['path_to_root'].$path) && (filemtime($hash) > filemtime($context['path_to_root'].$path))) {
			include_once $hash;
			return TRUE;
		}

		// also load the PHP version if there is no .mo file
		if(is_readable($hash) && !file_exists($context['path_to_root'].$path)) {
			include_once $hash;
			return TRUE;
		}

		// access file content
		if(!$handle = Safe::fopen($context['path_to_root'].$path, 'rb')) {

			// log information on development platform
			if($context['with_debug'] == 'Y')
				logger::remember('i18n/i18n.php', 'Impossible to load '.$path, '', 'debug');

			// we've got a problem
			return FALSE;
		}

		// read magic number
		$magic = array_shift(unpack('V', fread($handle, 4)));

		// byte ordering
		if($magic == (int)0x0950412de)
			$order = 'V'; // low endian
		elseif(dechex($magic) == 'ffffffff950412de')
			$order = 'V'; // low endian, yet negative
		elseif($magic == (int)0x0de120495)
			$order = 'N'; // big endian
		else {
			// log information on development platform
			if($context['with_debug'] == 'Y')
				logger::remember('i18n/i18n.php', 'bad magic number in '.$path, '', 'debug');

			// we've got a problem
			return FALSE;
		}

		// read revision number
		$revision = array_shift(unpack($order, fread($handle, 4)));

		// read number of strings
		$number_of_strings = array_shift(unpack($order, fread($handle, 4)));

		// read offset for table of original strings
		$original_table_offset = array_shift(unpack($order, fread($handle, 4)));

		// read offset for table of translated strings
		$translated_table_offset = array_shift(unpack($order, fread($handle, 4)));

		// two integers per string (offset and size)
		$count = $number_of_strings * 2;

		// read the index of original strings
		fseek($handle, $original_table_offset);
		$original_table = unpack($order.$count, fread($handle, ($count * 4)));

		// read the index of translated strings
		fseek($handle, $translated_table_offset);
		$translated_table = unpack($order.$count, fread($handle, ($count * 4)));

		// no cache if we are not allowed to write files
		if($cache = Safe::fopen($hash, 'w')) {

			// start the cache file --make it a reference file to ensure it is integrated into yacs built
			$cache_content = '<?php'."\n"
				.'/**'."\n"
				.' * cache localized strings'."\n"
				.' *'."\n"
				.' * This file has been created by the script i18n/i18n.php. Please do not modify it manually.'."\n"
				.' * @reference'."\n"
				.' */'."\n";

		}

		// read all pairs of string
		for($index = 0; $index < $number_of_strings; $index++) {

			// read original string
			fseek($handle, $original_table[$index * 2 + 2]);
			if(!$length = $original_table[$index * 2 + 1])
				$original = '_headers';
			else
				$original = fread($handle, $length);

			// read translated string
			fseek($handle, $translated_table[$index * 2 + 2]);
			if(!$length = $translated_table[$index * 2 + 1])
				$translated = '';
			else
				$translated = fread($handle, $length);

			// save in memory
			$hash =& i18n::hash($original);
			$_SESSION['l10n'][$language][$hash] = $translated;

			// escape original string
			$hash = str_replace('\000', "'.chr(0).'", addcslashes($hash, "\0\\'"));

			// escape translated string
			$translated = str_replace('\000', "'.chr(0).'", addcslashes($translated, "\0\\'"));

			// update cache file, if any
			if($cache)
				$cache_content .= '$_SESSION[\'l10n\'][\''.$language.'\'][\''.$hash.'\']=\''.$translated."';\n";
		}

		// clean out
		fclose($handle);

		// look for plural string
		if(preg_match('/plural-forms: ([^\n]*)\n/i', $_SESSION['l10n'][$language]['_headers'], $matches) && strcmp($matches[1], 'nplurals=INTEGER; plural=EXPRESSION;'))
			$plural = $matches[1];
		else
			$plural = 'nplurals=2; plural=(n != 1)';

		// save it in cache as well
		$_SESSION['l10n'][$language]['_plural'] = $plural;

		// finalize cache file, if any
		if($cache) {
			$cache_content .= '$_SESSION[\'l10n\'][\''.$language.'\'][\'_plural\']=\''.addcslashes($plural, "\\'")."';\n".'?>';
			fwrite($cache, $cache_content);
			fclose($cache);
		}

		// everything went fine
		return TRUE;
	}

	/**
	 * list all available locales
	 *
	 * @return array of ($locale => $label)
	 */
	function &list_locales() {
		global $context, $locales;

		// list of locales
		$locales = array();

		// one directory per locale
		if($dir = Safe::opendir($context['path_to_root'].'i18n/locale')) {
			while(($item = Safe::readdir($dir)) !== FALSE) {
				if(($item[0] == '.') || !is_dir($context['path_to_root'].'i18n/locale/'.$item))
					continue;

				// remember locale
				$locales[$item] = $item;

				// enhance with manifest file, if any
				if(is_readable($context['path_to_root'].'i18n/locale/'.$item.'/manifest.php'))
					include_once $context['path_to_root'].'i18n/locale/'.$item.'/manifest.php';
			}
			Safe::closedir($dir);

		} else
			logger::remember('i18n/i18n.php', 'Impossible to browse directory i18n/locale');

		// done
		return $locales;
	}

	/**
	 * localize in singular/plural as per community settings
	 *
	 * This function is equivalent to ngettext(), except that it localizes in the preferred language for the community.
	 *
	 * @param string singular form
	 * @param string plural form
	 * @param int number of items to consider
	 * @return string the localized string
	 */
	function &nc($singular, $plural, $count) {
		global $context;

		// sanity check
		$count = intval($count);
		if($count < 1)
			$count = 1;

		// select language used by community
		if(isset($context['preferred_language']))
			$locale = $context['preferred_language'];
		else
			$locale = 'en';

		// key in cache
		$text =& i18n::hash($singular.chr(0).$plural);

		// do it manually
		if(!isset($_SESSION['l10n']) || !is_array($_SESSION['l10n'][$locale]) || !array_key_exists($text, $_SESSION['l10n'][$locale]) || !array_key_exists('_plural', $_SESSION['l10n'][$locale])) {
			if($count != 1)
				return $plural;
			else
				return $singular;
		}

		// use cached plural definition
		$plural = $_SESSION['l10n'][$locale]['_plural'];

		// make a PHP statement out of it
		$plural = str_replace('nplurals','$total', $plural);
		$plural = str_replace('n', $count, $plural);
		$plural = str_replace('plural', '$select', $plural);

		// compute string index
		$total = 0;
		$select = 0;
		eval($plural);
		if($select >= $total)
			$select = $total - 1;

		// get translated strings
		$text = $_SESSION['l10n'][$locale][$text];

		// explode and select correct part
		$parts = explode(chr(0), $text);
		$text = $parts[$select];
		return $text;
	}

	/**
	 * localize in singular/plural for a surfer
	 *
	 * This function is equivalent to ngettext(), except that it localizes in the preferred language for the surfer.
	 *
	 * @param string singular form
	 * @param string plural form
	 * @param int number of items to consider
	 * @return string the localized string
	 */
	function &ns($singular, $plural, $count) {
		global $context;

		// sanity check
		$count = intval($count);
		if($count < 1)
			$count = 1;

		// select language used by surfer
		if(isset($context['language']))
			$locale = $context['language'];
		else
			$locale = 'en';

		// key in cache
		$text =& i18n::hash($singular.chr(0).$plural);

		// do it manually
		if(!isset($_SESSION['l10n']) || !isset($_SESSION['l10n'][$locale]) || !is_array($_SESSION['l10n'][$locale]) || !array_key_exists($text, $_SESSION['l10n'][$locale]) || !array_key_exists('_plural', $_SESSION['l10n'][$locale])) {
			if($count != 1)
				return $plural;
			else
				return $singular;
		}

		// use cached plural definition
		$plural = $_SESSION['l10n'][$locale]['_plural'];

		// make a PHP statement out of it
		$plural = str_replace('nplurals','$total', $plural);
		$plural = str_replace('n', $count, $plural);
		$plural = str_replace('plural', '$select', $plural);

		// compute string index
		$total = 0;
		$select = 0;
		eval($plural);
		if($select >= $total)
			$select = $total - 1;

		// get translated strings
		$text = $_SESSION['l10n'][$locale][$text];

		// explode and select correct part
		$parts = explode(chr(0), $text);
		$text = $parts[$select];
		return $text;
	}

	/**
	 * get a localized string for a surfer
	 *
	 * This function is an equivalent to gettext(), except it deals with surfer localization.
	 *
	 * @param string the template string to be translated
	 * @return string the localized string, if any
	 */
	function &s($text) {
		global $context;

		// sanity check
		if(!$text)
			return $text;

		// select language used by surfer
		if(isset($context['language']))
			$locale = $context['language'];
		else
			$locale = 'en';

		// cache is empty
		if(!isset($_SESSION['l10n']) || !isset($_SESSION['l10n'][$locale]) || !is_array($_SESSION['l10n'][$locale]))
			return $text;

		// provide the localized string
		$text =& i18n::lookup($_SESSION['l10n'][$locale], $text);
		return $text;
	}

	/**
	 * get a localized string for a background process
	 *
	 * This function also transcode HTML entities to Unicode entities, if any.
	 *
	 * @param string the label identifying string
	 * @return string the localized string, if any
	 */
	function &server($name) {
		global $context, $local;

		$text =& i18n::l($local, $name, $context['preferred_language']);
		return $text;
	}

	/**
	 * get a localized string for a surfer
	 *
	 * To localize strings internally you should put alternative strings in the $local array, and use
	 * this function to select the correct version.
	 *
	 * Example:
	 * [php]
	 * $local['mystring_en'] = 'my string';
	 * $local['mystring_fr'] = 'ma chaine de caracteres';
	 * $text = i18n::user('mystring');
	 * [/php]
	 *
	 * This function also transcode HTML entities to Unicode entities, if any.
	 *
	 * @param string the label identifying string
	 * @param string desired language, if any
	 * @return string the localized string, if any
	 */
	function &user($name, $forced='') {
		global $local;

		$text =& i18n::l($local, $name, $forced);
		return $text;
	}

}

// store strings localized internally -- to be used with i18n::l($local, '...')
global $local;
$local = array();

//
// legacy support
//

if(!function_exists('get_local')) {

	// localize for some user
	function get_local($label, $language=NULL) {
		return i18n::user($label, $language);
	}

}

if(!function_exists('get_preferred')) {

	// localize according to server settings
	function get_preferred($label) {
		return i18n::server($label);
	}

}

// load localized strings
i18n::bind('i18n');

?>