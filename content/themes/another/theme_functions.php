<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


function delta_time($timestamp) {
	if(time() - $timestamp > 3600 * 24)
		return ' on ' . date('D M d H:i:s Y', $timestamp);
	
	if(time() - $timestamp > 3600)
		return ' ' .date('G', time() - $timestamp). ' minutes ago';
		
	return ' ' .date('m', time() - $timestamp). ' minutes ago';
}