/**
* Loaded for IE browser < 10
* select here your blocks with CSS3 rules style
* like bow-shadow, rounded corners, or gradients
*
* @see http://css3pie.com/documentation/supported-css3-features/
*
* @author Alexis Raimbault
* @reference
* @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
*/

$(function() {
	if (window.PIE) {
		$('#upperbody, .navigation_box, .extra_box').addClass('pie');

		$('.pie').each(function() {
		PIE.attach(this);
		});
	}
});