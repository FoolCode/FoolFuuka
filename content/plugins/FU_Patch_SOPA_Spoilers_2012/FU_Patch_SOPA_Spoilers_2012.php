<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFuuka Patch SOPA Spoilers 2012 plugin
 *
 * This plugin removes the spoilers added by moot during the 
 * SOPA protest day, without needing to modify the database entries.
 *
 * @package        	FoOlFrame
 * @subpackage    	FoOlFuuka
 * @category    	Plugins
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class FU_Patch_SOPA_Spoilers_2012 extends Plugins_model
{
	
	function initialize_plugin()
	{
		$this->plugins->register_hook($this, 'fu_post_model_before_process_comment', 5, function($board, $post){

			// the comment checker may be running and timestamp may not be set, otherwise do the check
			if (isset($post->timestamp) && $post->timestamp > 1326840000 && $post->timestamp < 1326955000)
			{
				if (strpos($post->comment, '</spoiler>') > 0)
				{
					$post->comment = str_replace(array('[spoiler]', '[/spoiler]', '</spoiler>'), '', $post->comment);
				}
				
				if (preg_match('/^\[spoiler\].*\[\/spoiler\]$/s', $comment))
				{
					$post->comment = str_replace(array('[spoiler]', '[/spoiler]'), '', $post->comment);
				}
			}
			
			return array('parameters' => array($board, $post));
		});
	}
	
}