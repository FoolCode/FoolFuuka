<?php

namespace Foolfuuka\Plugins\Image_In_Html;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Plugin_Fu_Image_In_Html_Chan extends \Foolfuuka\Controller_Chan
{

	
	public function radix_image_html($filename)
	{
		// Check if $filename is valid.
		if ( ! in_array(\Input::extension(), array('gif', 'jpg', 'png')) || ! \Board::is_natural(substr($filename, 0, 13)))
		{
			return $this->action_404(__('The filename submitted is not compatible with the system.'));
		}

		try
		{
			$media = \Media::get_by_filename($this->_radix, $filename.'.'.\Input::extension());
		}
		catch (Model\MediaException $e)
		{
			return $this->action_404(__('The image was never in our databases.'));
		}

		if ($media->media_link !== null)
		{
			ob_start();
			?>
			<article class="full_image">
				<nav>
					<?php if ($media->total) : ?><a href="<?= \Uri::create($this->_radix->shortname.'/search/image/'.$media->safe_media_hash) ?>" class="btnr parent"><?= __('View Same') ?></a><?php endif; ?>
					<a href="http://google.com/searchbyimage?image_url=<?= $media->thumb_link ?>" target="_blank" class="btnr parent">Google</a>
					<a href="http://iqdb.org/?url=<?= $media->thumb_link ?>" target="_blank" class="btnr parent">iqdb</a>
					<a href="http://saucenao.ci'd om/search.php?url=<?= $media->thumb_link ?>" target="_blank" class="btnr parent">SauceNAO</a>
					<a href="#" class="btnr parent" style="background-color: #EF8B77; color: #fff" data-media-id="<?= $media->media_id ?>" data-board="<?= htmlspecialchars($this->_radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report_media"><?= __('Report') ?></a>
				</nav>
				<img src="<?= $media->get_link(false, true) ?>">
			</article>
			<style>
				.theme_default .full_image {
					margin: 10px auto;
					padding: 4px;
					text-align:center;
				}

				.theme_default .full_image nav {
					margin: 10px;
				}

				.theme_default .full_image nav .btnr {
					font-size: 20px
				}

				.theme_default .full_image img {
					max-width: 90%;
				}
				
				.theme_default article.thread article.post:nth-of-type(-n+4) {
					display:block;
					float:none;
					margin: 0px auto;
				}
				
				.theme_default .post {
					width: 50%;
					display:block;
					float:none;
					margin: 10px auto;
				}
			</style>
			<?php
			$content = ob_get_clean();
			
			$this->_theme->bind('section_title', \Str::tr(__('Displaying image :image_filename'), array('image_filename' => $media->media)));
			
			try
			{
				$board = \Search::forge()
					->get_search(array('image' => $media->media_hash))
					->set_radix($this->_radix)
					->set_options('limit', 5)
					->set_page(1);

				$comments = $board->get_comments();
			}
			catch (\Foolfuuka\Model\SearchException $e)
			{
				return $this->error($e->getMessage());
			}
			catch (\Foolfuuka\Model\SearchEmptyResultException $e)
			{
				$comments = array();
			}
			catch (\Foolfuuka\Model\BoardException $e)
			{
				return $this->error($e->getMessage());
			}
			
			$image_html = $this->_theme->build('plugin', array('content' => $content), true);
			$board_html = $this->_theme->build('board', array('board' => $comments, 'disable_default_after_headless_open' => true), true);
			return \Response::forge($this->_theme->build('plugin', array('content' => $image_html.$board_html)));
		}
	
		return \Response::redirect(
			\Uri::create(array($this->_radix->shortname, 'search', 'image', rawurlencode(substr($media->media_hash, 0, -2)))), 'location', 404);
	}
	
	
}