<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-gifsicle')
	->setCall(function($result) {

		\Foolz\Plugin\Event::forge('fu.model.media.insert.resize')
			->setCall(function($result){

				if ( ! $result instanceof \Foolz\Plugin\Void || strtolower($this->temp_extension) !== 'gif')
				{
					// someone is already done with the image or it's not a gif
					return;
				}

				exec("gifsicle ".$result->getParam('full_path')." --colors 256 --color-method blend-diversity"
					." --resize-fit ".$result->getParam('thumb_width')."x".$result->getParam('thumb_height')
					." --dither > ".$this->path_from_filename(true, $result->getParam('is_op'), true));

				// only change the result so it's not void
				$result->set('done');

			});
	});