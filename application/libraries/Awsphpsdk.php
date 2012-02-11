<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
 
class AwsPhpSdk
{
	var $CI;
	
	function __construct()
	{
		ini_set('include_path', ini_get('include_path').';'.PATH_SEPARATOR . APPPATH . 'libraries/awsphpsdk');
		require_once('sdk.class.php');
		
		// Obtain a reference to the ci super object
		$this->CI =& get_instance();
		
		// Load AWS configurations
		$this->CI->load->config('aws');
	}
}


/* End of file Awsphpsdk.php */
/* Location: ./application/libraries/Awsphpsdk.php */