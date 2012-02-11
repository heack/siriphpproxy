<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

class Base_Controller extends CI_Controller
{
	// Output formats
    private $output_formats = array(
		'json' 	=> 'application/json'
    );

	/*
	// Incoming supported content types
	private $supported_formats = array(
		'form-data' => 'multipart/form-data'
	);		
	
    private $request = NULL;
    private $put = array();
	private $delete = array();
	 */
	const ADMIN_ACCOUNT_TYPE = 1;
	public $account = NULL;
	public $token = NULL;
    public $client = NULL;
	
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
	
	/*
	private function detect_input_format()
	{
		if ($this->input->server('CONTENT_TYPE'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach ($this->supported_formats as $format => $mime)
			{
				if (strpos($match = $this->input->server('CONTENT_TYPE'), ';'))
				{
					$match = current(explode(';', $match));
				}

				if ($match == $mime)
				{
					return $format;
				}
			}
		}

		return NULL;
	}
	 */

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
	 * Check for administrator account
	 */
	public function is_admin($account_id)
	{
		$this->load->model('a3/account_model');
		
		if ($this->account_model->check_account_type($account_id, self::ADMIN_ACCOUNT_TYPE)) return TRUE;
	}
			
	/**
	 * Check for valid oauth client credentials
	 */
	public function oauth_client()
	{
		$this->load->library('oauthserver');
		
		if ($this->params('client_secret'))
		{
			$this->client = $this->oauthserver->find_client_by_secret($this->params('client_secret'));
		}
	}

	/**
	 * Check for valid oauth client and resource owner credentials
	 */
	public function oauth_account()
	{
		$this->oauth_client();
		
		if ($this->client && $this->params('access_token'))
		{
			if ($this->token = $this->oauthserver->find_token_by_client_id_and_access_token($this->client->id, $this->params('access_token')))
			{
				$this->load->model('a3/account_model');
				$this->account = $this->account_model->find_by_id($this->token->a3_account_id);
			}
		}
	}
	
	/**
	 * Require a client
	 */
	public function require_client()
	{
		$this->oauth_client();
				
		if ( ! $this->client)
		{
			header("Content-Type: application/json");
			header("Cache-Control: no-store");
			header("HTTP/1.1 ". 403);
			
			// Output json and die									
			echo json_encode(array('message' => 'Invalid oauth client credentials.'));	
			die;						
		}
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
		// For PUT requests obtain paramaters from stream
		// if ($_SERVER['REQUEST_METHOD'] == 'PUT')
		// {
			// parse_str(file_get_contents("php://input"), $_POST);
		// }
		
		if ($this->input->get('snb_region')) $region = $this->input->get('snb_region');
		if ($this->input->post('snb_region')) $region = $this->input->post('snb_region');
		if (!isset($region))
		{
			$region = 'sg';
		}
		
		$this->load->helper('date');
		$log = array(
			'region' => $region,
			'http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : NULL,
			'remote_addr' => $this->remote_ip_address,
			'remote_host' => isset($_SERVER['REMOTE_HOST']) ? trim($_SERVER['REMOTE_HOST']) : NULL,
			'request_uri' => isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI']) : NULL,
			'request_method' => isset($_SERVER['REQUEST_METHOD']) ? trim($_SERVER['REQUEST_METHOD']) : NULL,
			'get_data' => $_GET ? json_encode($_GET) : NULL,
			'post_data' => $_POST ? json_encode($_POST) : NULL,
			'body_data' => $body,
			'created_at' => mdate('%Y-%m-%d %H:%i:%s', now()),
		);

 		$this->load->library('awsphpsdk');
		if ($this->config->item('enable_api_logging'))
		{
			$sqs = new AmazonSQS();
			$sqs->set_region(AmazonSQS::REGION_APAC_SE1);
			if ( ! $this->config->item('enable_ssl_verification')) $sqs->ssl_verification = FALSE;
			$response = $sqs->send_message($this->config->item('aws_sqs_queue_url'), json_encode($log));
						
			if ( ! $response->isOK())
			{
				// Sue Amazon or just CRY LOUDLY...
				log_message('error', "AWS SQS Queue Send Message Failed");
			}
		}
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
	
	/*
	private function parse_raw_http_request()
	{
		$input = file_get_contents('php://input');

		// get the multipart data boundry
		preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
		$boundary = $matches[1];

		// split the content
		$a_blocks = preg_split("/-+$boundary/", $input);
		array_pop($a_blocks);

		foreach ($a_blocks as $id => $block)
		{
			if (empty($block)) continue;
			
			// parse uploaded files
			if (strpos($block, 'Content-Type:') !== FALSE)
			{
				preg_match("/name=\"([^\"]*)\".*Content-Type:.*?[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
			}
			// parse data
			else
			{
				preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
			}
			$this->put[$matches[1]] = $matches[2];
		}        
	}
	 */
}
