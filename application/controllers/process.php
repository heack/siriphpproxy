<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Process extends Base_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	
	var $before_filter = array(
	);
    var $after_filter = array();
	
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
	{
		$this->load->view('welcome_message');
	}
	public function responseto($term) {
		$result = "hello ".$term;
		$this->response(array('results'=>$result));		
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */