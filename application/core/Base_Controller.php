<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

class Base_Controller extends CI_Controller
{
	// Output formats
    private $output_formats = array(
		'json' 	=> 'application/json'
    );
	
	public $remote_ip_address = NULL;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->find_remote_ip();
        
        //$this->request->method = $this->detect_request_method();
		
		/*
		// $this->request->format = $this->detect_input_format();
		
		switch ($this->request->method)
		{
			case 'put':
				// if ($this->request->format)
				// {
					// $this->parse_raw_http_request();
				// }
				// else
				// {
					parse_str(file_get_contents('php://input'), $this->put);
				// }
			break;
			
			case 'delete':
				parse_str(file_get_contents('php://input'), $this->delete);
			break;
		}
		 */
	}
	
	private function find_remote_ip()
	{
		// Obtain remote ip address from $_SERVER['HTTP_X_FORWARDED_FOR']
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$x_forwarded_for = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip = trim($x_forwarded_for[sizeof($x_forwarded_for)-1]);
			if($this->input->valid_ip($ip))
			{
				$this->remote_ip_address = $ip;
			}
		}
		else
		{
			$this->remote_ip_address = $this->input->ip_address();
		}		
	}
	
    /**
     * This function detects the HTTP request method
     */
    private function detect_request_method()
    {
    	$method = strtolower($this->input->server('REQUEST_METHOD'));
		
    	if (in_array($method, array('get', 'delete', 'post', 'put')))
    	{
	    	return $method;
    	}

    	return 'get';
    }
	
	

    /**
     *  A wrapper for CI GET input
     */
    public function get($key)
    {
    	return $this->input->get($key, TRUE);
    }
    
    /**
     *  A wrapper for CI POST input
     */
    public function post($key)
    {
    	return $this->input->post($key, TRUE);
    }
	
    /**
     *  A wrapper for HTTP PUT input
    public function put($key)
    {
    	return array_key_exists($key, $this->put) ? $this->security->xss_clean($this->put[$key]) : FALSE;
    }
     */
		
    /**
     *  A wrapper for HTTP DELETE input
    public function delete($key)
    {
    	return array_key_exists($key, $this->delete) ? $this->security->xss_clean($this->delete[$key]) : FALSE;
    }
     */
	
	public function params($key)
	{
		switch ($this->detect_request_method())
		{
			case 'get': return $this->get($key); break;
			case 'post': return $this->post($key); break;
			/*
			case 'put': return $this->put($key); break;
			case 'delete': return $this->delete($key); break;
			 */
		}						
	}
	
	
			
	/**
	 * Check for valid oauth client credentials
	 */
	public function oauth_client()
	{
		
	}

	/**
	 * Check for valid oauth client and resource owner credentials
	 */
	
	
	/**
	 * Require a client
	 */
	public function require_client()
	{
		
	}
	
	/**
	 * Require a client and an account
	 */
	public function require_account()
	{
		$this->require_client();
				
		$this->oauth_account();
		
		if ( ! $this->account)
		{
			header("Content-Type: application/json");
			header("Cache-Control: no-store");
			header("HTTP/1.1 ". 403);
			
			// Output json and die									
			echo json_encode(array('message' => 'Invalid oauth token credentials.'));	
			die;
		}
	}
	
	/*
	 * Log API usage to AWS SQS
	 */
	private function logger($body)
	{
		
	}
	
	/**
	 * @params array $data
	 * @params int $status_code
	 */
    public function response($data, $status_code = 200)
    {
        foreach($data as $k=>$v)
        {
            switch($k)
            {
               	case 'code':
                    $output_data['code'] = $v;
                break;

                case 'message':
                    $output_data['message'] = $v;
                break;

                case 'total':
                    $output_data['total'] = $v;
                break;

                case 'results':
                    $output_data['results'] = $v;
                break;

                case 'errors':
                    $output_data['errors'] = $v;
                break;

                default:
                    $output_data[$k] = $v;
            }
        }
		
        if (isset($output_data))
        {
            // set http response header
            $this->output->set_status_header($status_code);
            $output = json_encode($output_data);
        }
        else
        {
            // set http response header
            $this->output->set_status_header(500);
            $output = json_encode(array('Internal server error: Try again later.'));
        }
		
		// set output content type
		$this->output->set_header('Content-Type: '.$this->output_formats['json'].'; charset=utf-8');        
		
		// log the request
		$this->logger($output);
		
		// send output
		$this->output->set_output($output);
    }
	
}
