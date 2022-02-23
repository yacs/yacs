/** 
 * To be defered within your pages if you need to record events :
 * You can put this script in included/browser/js_endpage or call
 * it specifically with Page::defer_script
 * @see skin/page.php
 * 
 * @author devalxr
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

var matomoevent = {
    
    target : url_to_root + 'included/tomato/event.ajax.php',
    
    rec : function(category, action, name = false, value = false) {
        
        $.post(
              matomoevent.target,
              
              {
                  category: category,
                  action : action,
                  name : name,
                  value : value
              }
        );
        
    }
    
};