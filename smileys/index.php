<?php
/**
 * use smileys to share your feeling
 *
 * For a comprehensive list of supported smileys, visit [script]smileys/smileys.php[/script].
 *
 * Smileys are these little icons that we are using to briefly report on our feeling.
 *
 * This index page reports on [link=all smileys that are known on this server]smileys/index.php[/link].
 *
 * To add a new smiley on this system, you will have:
 * - to prepare a new icon file
 * - to name it
 * - to update smileys.php to bind the name to the file
 * - to update the page below to show it working
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'smileys.php';

// load localized strings
i18n::bind('smileys');

// load the skin
load_skin('smileys');

// the path to this page
$context['path_bar'] = array( 'help.php' => i18n::s('Help index') );

// the title of the page
$context['page_title'] = i18n::s('Index of smileys');

// the date of last modification
if(Surfer::is_associate())
	$context['text'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// the splash message
$context['text'] .= '<p>'.i18n::s('Smileys are small graphical images that can be used to convey an emotion or feeling. If you have used email or Internet chat, you are likely familiar with the smilie concept.').'</p>'
	.'<p>'.i18n::s('Let face it, sometimes words alone do not suffice. Adding a winking smilie, for instance, may help you clarify that you are joking.').'</p>'
	.'<p>'.i18n::s('Use your smilies sparingly, though -- if overused, smilies can be downright annoying.').'</p>'
	.'<p>'.i18n::s('Here is the list of codes that are automatically converted into images by this server.').'</p>';

// use a table for the layout
$context['text'] .= Skin::table_prefix('grid');
$cells = array();
$cells[] = i18n::s('What to type');
$cells[] = i18n::s('Emotion');
$cells[] = i18n::s('Featured image');
$context['text'] .= Skin::table_row($cells, 'header');
$lines = 2;

// 8) 8-) :cool:
$cells = array();
$cells[] = '[escape]8&#41; 8-&#41; :cool:[/escape]';
$cells[] = i18n::s('sunglass dude');
$cells[] = '8-)';
$context['text'] .= Skin::table_row($cells, $lines++);

// :) :-) :smile:
$cells = array();
$cells[] = '[escape]:&#41; :-&#41; :smile:[/escape]';
$cells[] = i18n::s('smile');
$cells[] = ':-)';
$context['text'] .= Skin::table_row($cells, $lines++);

// ::) ::-) :rolleyes:
$cells = array();
$cells[] = '[escape]::&#41; ::-&#41; :rolleyes:[/escape]';
$cells[] = i18n::s('roll eyes');
$cells[] = '::-)';
$context['text'] .= Skin::table_row($cells, $lines++);

// ;) ;-) :wink:
$cells = array();
$cells[] = '[escape];&#41; ;-&#41; :wink:[/escape]';
$cells[] = i18n::s('wink/sarcasm');
$cells[] = ';-)';
$context['text'] .= Skin::table_row($cells, $lines++);

// :D :-D :cheesy:
$cells = array();
$cells[] = '[escape]:D :-D :cheesy:[/escape]';
$cells[] = i18n::s('cheesy');
$cells[] = ':-D';
$context['text'] .= Skin::table_row($cells, $lines++);

// :lol:
$cells = array();
$cells[] = '[escape]:lol:[/escape]';
$cells[] = i18n::s('rolling on the floor, laughing');
$cells[] = ':lol:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :P :-P :tongue:
$cells = array();
$cells[] = '[escape]:P :-P :tongue:[/escape]';
$cells[] = i18n::s('tongue');
$cells[] = ':-P';
$context['text'] .= Skin::table_row($cells, $lines++);

// :( :-( :frown:
$cells = array();
$cells[] = '[escape] :( :-( :frown:[/escape]';
$cells[] = i18n::s('frown');
$cells[] = ':-(';
$context['text'] .= Skin::table_row($cells, $lines++);

// >:( >:-( :angry:
$cells = array();
$cells[] = '[escape] >:( >:-( :angry:[/escape]';
$cells[] = i18n::s('angry');
$cells[] = '>:-(';
$context['text'] .= Skin::table_row($cells, $lines++);

// :basket:
$cells = array();
$cells[] = '[escape] :basket:[/escape]';
$cells[] = i18n::s('to the basket');
$cells[] = ':basket:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :bomb:
$cells = array();
$cells[] = '[escape] :bomb:[/escape]';
$cells[] = i18n::s('bomb');
$cells[] = ':bomb:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :pissed:
$cells = array();
$cells[] = '[escape] :pissed:[/escape]';
$cells[] = i18n::s('pissed off');
$cells[] = ':pissed:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :insult:
$cells = array();
$cells[] = '[escape] :insult:[/escape]';
$cells[] = i18n::s('enough is enough');
$cells[] = ':insult:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :gun:
$cells = array();
$cells[] = '[escape] :gun:[/escape]';
$cells[] = i18n::s('i will shot you');
$cells[] = ':gun:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :chainsaw:
$cells = array();
$cells[] = '[escape] :chainsaw:[/escape]';
$cells[] = i18n::s('i will kill you');
$cells[] = ':chainsaw:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :'( :'-( :cry:
$cells = array();
$cells[] = '[escape] :\'( :\'-( :cry:[/escape]';
$cells[] = i18n::s('cry');
$cells[] = ':\'-(';
$context['text'] .= Skin::table_row($cells, $lines++);

// :o :-o :shocked:
$cells = array();
$cells[] = '[escape] :o :-o :shocked:[/escape]';
$cells[] = i18n::s('shocked');
$cells[] = ':-o';
$context['text'] .= Skin::table_row($cells, $lines++);

// :horror:
$cells = array();
$cells[] = '[escape]:horror:[/escape]';
$cells[] = i18n::s('ekk/horror!');
$cells[] = ':horror:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :X :-X :sealed:
$cells = array();
$cells[] = '[escape] :X :-X :sealed:[/escape]';
$cells[] = i18n::s('sealed');
$cells[] = ':-X';
$context['text'] .= Skin::table_row($cells, $lines++);

// :paranoid:
$cells = array();
$cells[] = '[escape]:paranoid:[/escape]';
$cells[] = i18n::s('this is an unsafe place');
$cells[] = ':paranoid:';
$context['text'] .= Skin::table_row($cells, $lines++);

// ??? or :confused:
$cells = array();
$cells[] = '[escape]&#63;&#63;&#63; :confused:[/escape]';
$cells[] = i18n::s('confused');
$cells[] = '???';
$context['text'] .= Skin::table_row($cells, $lines++);

// :/ :-/ :undecided:
$cells = array();
$cells[] = '[escape] :/ :-/ :undecided:[/escape]';
$cells[] = i18n::s('undecided');
$cells[] = ':-/';
$context['text'] .= Skin::table_row($cells, $lines++);

// :[ :-[ :embarassed:
$cells = array();
$cells[] = '[escape] :[ :-[ :embarassed:[/escape]';
$cells[] = i18n::s('embarassed');
$cells[] = ':-[';
$context['text'] .= Skin::table_row($cells, $lines++);

// :blush:
$cells = array();
$cells[] = '[escape]:blush:[/escape]';
$cells[] = i18n::s('you make me blush');
$cells[] = ':blush:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :alien:
$cells = array();
$cells[] = '[escape]:alien:[/escape]';
$cells[] = i18n::s('alien');
$cells[] = ':alien:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :bounce:
$cells = array();
$cells[] = '[escape]:bounce:[/escape]';
$cells[] = i18n::s('bounce');
$cells[] = ':bounce:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :hot:
$cells = array();
$cells[] = '[escape]:hot:[/escape]';
$cells[] = i18n::s('hot bounce');
$cells[] = ':hot:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :angel:
$cells = array();
$cells[] = '[escape]:angel:[/escape]';
$cells[] = i18n::s('angel');
$cells[] = ':angel:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :clap:
$cells = array();
$cells[] = '[escape]:clap:[/escape]';
$cells[] = i18n::s('clap clap clap, etc.');
$cells[] = ':clap:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :king:
$cells = array();
$cells[] = '[escape]:king:[/escape]';
$cells[] = i18n::s('The King');
$cells[] = ':king:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :beer:
$cells = array();
$cells[] = '[escape]:beer:[/escape]';
$cells[] = i18n::s('let have some beer');
$cells[] = ':beer:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :bionic:
$cells = array();
$cells[] = '[escape]:bionic:[/escape]';
$cells[] = i18n::s('some electronic inside');
$cells[] = ':bionic:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :idiot:
$cells = array();
$cells[] = '[escape]:idiot:[/escape]';
$cells[] = i18n::s('shaky brain');
$cells[] = ':idiot:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :ghost:
$cells = array();
$cells[] = '[escape]:ghost:[/escape]';
$cells[] = i18n::s('ghost');
$cells[] = ':ghost:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :frog:
$cells = array();
$cells[] = '[escape]:frog:[/escape]';
$cells[] = i18n::s('frog');
$cells[] = ':frog:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :female:
$cells = array();
$cells[] = '[escape]:female:[/escape]';
$cells[] = i18n::s('female');
$cells[] = ':female:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :male:
$cells = array();
$cells[] = '[escape]:male:[/escape]';
$cells[] = i18n::s('male');
$cells[] = ':male:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :colored:
$cells = array();
$cells[] = '[escape]:colored:[/escape]';
$cells[] = i18n::s('colored');
$cells[] = ':colored:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :chinese:
$cells = array();
$cells[] = '[escape]:chinese:[/escape]';
$cells[] = i18n::s('asian salutation');
$cells[] = ':chinese:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :crazy:
$cells = array();
$cells[] = '[escape]:crazy:[/escape]';
$cells[] = i18n::s('this does not make sense');
$cells[] = ':crazy:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :naive:
$cells = array();
$cells[] = '[escape]:naive:[/escape]';
$cells[] = i18n::s('you are so naive');
$cells[] = ':naive:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :sick:
$cells = array();
$cells[] = '[escape]:sick:[/escape]';
$cells[] = i18n::s('feeling bad');
$cells[] = ':sick:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :dead:
$cells = array();
$cells[] = '[escape]:dead:[/escape]';
$cells[] = i18n::s('I am killed');
$cells[] = ':dead:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :wall:
$cells = array();
$cells[] = '[escape]:wall:[/escape]';
$cells[] = i18n::s('we will hit the wall');
$cells[] = ':wall:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :hip:
$cells = array();
$cells[] = '[escape]:hip:[/escape]';
$cells[] = i18n::s('hip');
$cells[] = ':hip:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :joker:
$cells = array();
$cells[] = '[escape]:joker:[/escape]';
$cells[] = i18n::s('joker');
$cells[] = ':joker:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :drool:
$cells = array();
$cells[] = '[escape]:drool:[/escape]';
$cells[] = i18n::s('I would like to get it');
$cells[] = ':drool:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :peeping:
$cells = array();
$cells[] = '[escape]:peeping:[/escape]';
$cells[] = i18n::s('peeping');
$cells[] = ':peeping:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :yak:
$cells = array();
$cells[] = '[escape]:yak:[/escape]';
$cells[] = i18n::s('bla bla bla');
$cells[] = ':yak:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :shouting:
$cells = array();
$cells[] = '[escape]:shouting:[/escape]';
$cells[] = i18n::s('i want to be sure you are hearing me');
$cells[] = ':shouting:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :reading:
$cells = array();
$cells[] = '[escape]:reading:[/escape]';
$cells[] = i18n::s('reading');
$cells[] = ':reading:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :sniping:
$cells = array();
$cells[] = '[escape]:sniping:[/escape]';
$cells[] = i18n::s('sniping');
$cells[] = ':sniping:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :clock:
$cells = array();
$cells[] = '[escape]:clock:[/escape]';
$cells[] = i18n::s('clock');
$cells[] = ':clock:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :pet:
$cells = array();
$cells[] = '[escape]:pet:[/escape]';
$cells[] = i18n::s('pet');
$cells[] = ':pet:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :dog:
$cells = array();
$cells[] = '[escape]:dog:[/escape]';
$cells[] = i18n::s('dog');
$cells[] = ':dog:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :cat:
$cells = array();
$cells[] = '[escape]:cat:[/escape]';
$cells[] = i18n::s('cat');
$cells[] = ':cat:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :email:
$cells = array();
$cells[] = '[escape]:email:[/escape]';
$cells[] = i18n::s('email');
$cells[] = ':email:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :film:
$cells = array();
$cells[] = '[escape]:film:[/escape]';
$cells[] = i18n::s('film');
$cells[] = ':film:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :note:
$cells = array();
$cells[] = '[escape]:note:[/escape]';
$cells[] = i18n::s('note');
$cells[] = ':note:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :phone:
$cells = array();
$cells[] = '[escape]:phone:[/escape]';
$cells[] = i18n::s('phone');
$cells[] = ':phone:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :present:
$cells = array();
$cells[] = '[escape]:present:[/escape]';
$cells[] = i18n::s('present');
$cells[] = ':present:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :rose:
$cells = array();
$cells[] = '[escape]:rose:[/escape]';
$cells[] = i18n::s('rose');
$cells[] = ':rose:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :unlove:
$cells = array();
$cells[] = '[escape]:unlove:[/escape]';
$cells[] = i18n::s('unlove');
$cells[] = ':unlove:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :sleep:
$cells = array();
$cells[] = '[escape]:sleep:[/escape]';
$cells[] = i18n::s('a little nap');
$cells[] = ':sleep:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :sunshine:
$cells = array();
$cells[] = '[escape]:sunshine:[/escape]';
$cells[] = i18n::s('sunshine');
$cells[] = ':sunshine:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :thinking:
$cells = array();
$cells[] = '[escape]:thinking:[/escape]';
$cells[] = i18n::s('thinking');
$cells[] = ':thinking:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :hail:
$cells = array();
$cells[] = '[escape]:hail:[/escape]';
$cells[] = i18n::s('god bless you');
$cells[] = ':hail:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :yippee:
$cells = array();
$cells[] = '[escape]:yippee:[/escape]';
$cells[] = i18n::s('i am feeling so glad');
$cells[] = ':yippee:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :candle:
$cells = array();
$cells[] = '[escape] :candle:[/escape]';
$cells[] = i18n::s('candle');
$cells[] = ':candle:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :medal:
$cells = array();
$cells[] = '[escape]:medal:[/escape]';
$cells[] = i18n::s('this deserves a full medal');
$cells[] = ':medal:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :half_medal:
$cells = array();
$cells[] = '[escape]:half_medal:[/escape]';
$cells[] = i18n::s('this deserves half of a medal');
$cells[] = ':half_medal:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :no:
$cells = array();
$cells[] = '[escape]:no:[/escape]';
$cells[] = i18n::s('you should not do that');
$cells[] = ':no:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :forbidden:
$cells = array();
$cells[] = '[escape]:forbidden:[/escape]';
$cells[] = i18n::s('please stop');
$cells[] = ':forbidden:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :wave:
$cells = array();
$cells[] = '[escape]:wave:[/escape]';
$cells[] = i18n::s('good bye');
$cells[] = ':wave:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :flush:
$cells = array();
$cells[] = '[escape]:flush:[/escape]';
$cells[] = i18n::s('flush and forget it');
$cells[] = ':flush:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :shit:
$cells = array();
$cells[] = '[escape]:shit:[/escape]';
$cells[] = i18n::s('it stinks');
$cells[] = ':shit:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :diablotin:
$cells = array();
$cells[] = '[escape]:diablotin:[/escape]';
$cells[] = i18n::s('the dark side of The Force');
$cells[] = ':diablotin:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :trooper:
$cells = array();
$cells[] = '[escape]:trooper:[/escape]';
$cells[] = i18n::s('Star wars');
$cells[] = ':trooper:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :vader:
$cells = array();
$cells[] = '[escape]:vader:[/escape]';
$cells[] = i18n::s('Star wars');
$cells[] = ':vader:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :santa:
$cells = array();
$cells[] = '[escape]:santa:[/escape]';
$cells[] = i18n::s('Santa Claus');
$cells[] = ':santa:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :+ :up:
$cells = array();
$cells[] = '[escape]:+ :up:[/escape]';
$cells[] = i18n::s('good');
$cells[] = ' :+';
$context['text'] .= Skin::table_row($cells, $lines++);

// :- :down:
$cells = array();
$cells[] = '[escape]:- :down:[/escape]';
$cells[] = i18n::s('bad');
$cells[] = ' :-';
$context['text'] .= Skin::table_row($cells, $lines++);

// :peace:
$cells = array();
$cells[] = '[escape]:peace:[/escape]';
$cells[] = i18n::s('peace');
$cells[] = ':peace:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :yinyang:
$cells = array();
$cells[] = '[escape]:yinyang:[/escape]';
$cells[] = i18n::s('yin yang');
$cells[] = ':yinyang:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :heart:
$cells = array();
$cells[] = '[escape]:heart:[/escape]';
$cells[] = i18n::s('with love');
$cells[] = ':heart:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :sweetheart:
$cells = array();
$cells[] = '[escape]:sweetheart:[/escape]';
$cells[] = i18n::s('lovely');
$cells[] = ':sweetheart:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :* :-* :kiss:
$cells = array();
$cells[] = '[escape]:* :kiss:[/escape]';
$cells[] = i18n::s('kiss');
$cells[] = ':-*';
$context['text'] .= Skin::table_row($cells, $lines++);

// :love:
$cells = array();
$cells[] = '[escape]:love:[/escape]';
$cells[] = i18n::s('I want you');
$cells[] = ':love:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :hearts:
$cells = array();
$cells[] = '[escape]:hearts:[/escape]';
$cells[] = i18n::s('with (more) love');
$cells[] = ':hearts:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :cake:
$cells = array();
$cells[] = '[escape]:cake:[/escape]';
$cells[] = i18n::s('share a cake');
$cells[] = ':cake:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :camera:
$cells = array();
$cells[] = '[escape]:camera:[/escape]';
$cells[] = i18n::s('take a shot');
$cells[] = ':camera:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :cup:
$cells = array();
$cells[] = '[escape]:cup:[/escape]';
$cells[] = i18n::s('a cup of coffee');
$cells[] = ':cup:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :cocktail:
$cells = array();
$cells[] = '[escape]:cocktail:[/escape]';
$cells[] = i18n::s('have a cocktail');
$cells[] = ':cocktail:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :party:
$cells = array();
$cells[] = '[escape]:party:[/escape]';
$cells[] = i18n::s('have a party');
$cells[] = ':party:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :fairy:
$cells = array();
$cells[] = '[escape]:fairy:[/escape]';
$cells[] = i18n::s('once upon a time...');
$cells[] = ':fairy:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :? & :?2
$cells = array();
$cells[] = '[escape]:? &  :?2[/escape]';
$cells[] = i18n::s('question');
$cells[] = 'left= :? & :?2';
$context['text'] .= Skin::table_row($cells, $lines++);

// :?!
$cells = array();
$cells[] = '[escape]:?![/escape]';
$cells[] = i18n::s('idea');
$cells[] = ':?!';
$context['text'] .= Skin::table_row($cells, $lines++);

// :! & :!2
$cells = array();
$cells[] = '[escape]:! & :!2[/escape]';
$cells[] = i18n::s('warning');
$cells[] = 'left= :! & :!2';
$context['text'] .= Skin::table_row($cells, $lines++);

// :*:
$cells = array();
$cells[] = '[escape]:*:[/escape]';
$cells[] = i18n::s('star');
$cells[] = ':*:';
$context['text'] .= Skin::table_row($cells, $lines++);

// :mac
$cells = array();
$cells[] = '[escape]:mac: :tux: :win:[/escape]';
$cells[] = i18n::s('operating systems');
$cells[] = ':mac: :tux: :win:';
$context['text'] .= Skin::table_row($cells, $lines++);

// Get out
$cells = array();
$cells[] = '[escape]:out:[/escape]';
$cells[] = i18n::s('Ok, i leave');
$cells[] = ':out:';
$context['text'] .= Skin::table_row($cells, $lines++);

// end of the table
$context['text'] .= Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// referrals, if any
if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

	$cache_id = 'smileys/index.php#referrals#';
	if(!$text =& Cache::get($cache_id)) {

		// box content in a sidebar box
		include_once '../agents/referrals.php';
		if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].'smileys/index.php'))
			$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

		// save in cache for one hour 60 * 60 = 3600
		Cache::put($cache_id, $text, 'referrals', 3600);

	}

	// in the extra panel
	$context['extra'] .= $text;
}

// render the skin
render_skin();

?>