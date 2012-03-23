<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class MY_Image_lib extends CI_Image_lib
{
	
	function __construct($id = NULL)
	{
		parent::__construct();
	}
	
	
	function image_process_imagemagick($action = 'resize')
	{
		if($action != 'resize' || strtolower(pathinfo($this->full_src_path, PATHINFO_EXTENSION)) != 'gif')
		{echo 'here'; die();
			return parent::image_process_imagemagick($action);
		}	
			
		//  Do we have a vaild library path?
		if ($this->library_path == '')
		{
			$this->set_error('imglib_libpath_invalid');
			return FALSE;
		}

		if ( ! preg_match("/convert$/i", $this->library_path))
		{
			$this->library_path = rtrim($this->library_path, '/').'/';

			$this->library_path .= 'convert';
		}

		// Execute the command
		$cmd = $this->library_path." -quality ".$this->quality;
					
		// coalescing filename
		$coalesce = pathinfo($this->full_dst_path, PATHINFO_DIRNAME) . '/coalesce_' . pathinfo($this->full_dst_path, PATHINFO_BASENAME);
		
		$retval = 1;
		
		// coalesce first
		@exec($cmd . ' "' . $this->full_src_path . '" -coalesce -layers OptimizePlus "'. $coalesce .'"', $output, $retval);

		//	Did it work?
		if ($retval > 0)
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}
		
		$cmd .= " -resize ".$this->width."x".$this->height." \"$coalesce\" \"$this->full_dst_path\" 2>&1";

		$retval = 1;

		@exec($cmd, $output, $retval);
		
		unlink($coalesce);
		
		//	Did it work?
		if ($retval > 0)
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}

		// Set the file to 777
		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return TRUE;
	}
	
}