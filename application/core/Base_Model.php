<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Base_model extends CI_Model
{
	public $db_suffix = 'sg';
    
    public $errors;
	
    function __construct()
    {
        parent::__construct();
		
		// Detect region
		$this->detect_region();
		
	}
	
	/**
	 * Helper function for inserting and updating
	 */
	public function prepare_modification($data)
	{
		$flag = FALSE;
		
		foreach($data as $k => $v)
		{
			if($v) 
			{
				$this->db->set($k, $v);
				$flag = TRUE;
			}
		}
		
		return $flag;
	}
	
	public function detect_region()
	{
		if ($this->input->get('snb_region')) $region = $this->input->get('snb_region');
		if ($this->input->post('snb_region')) $region = $this->input->post('snb_region');
		
		if (isset($region) && in_array($region, $this->find_all_region()))
		{
			$this->db_suffix = $region;
		}
	}
	
	public function find_all_region()
	{
		return array('sg','in');
	}
	
    public function validate($obj)
    {
        $this->errors = NULL;
        if ( !empty($obj) && is_array($obj))
        {
            foreach ($obj as $item)
            {
                $rules = array_keys($item['rule']);
                
                foreach ($rules as $rule)
                {
                    if (is_numeric($rule))
                    {
                        $rule = $item['rule'][$rule];
                    }

                    switch ($rule)
                    {
                        case 'required' : $this->is_required($item); break;
                        case 'min_length' : $this->min_length($item); break;
                        case 'max_length' : $this->max_length($item); break;
                        case 'numeric' : $this->is_numeric($item); break;
                    }
                    
                    if (isset($this->errors[$item['field']]))
                    {
                        break;
                    }
                }
            }
        }
        
        return FALSE;
    }
    
    /**
     * Is required validation
     */
    private function is_required($item)
    {
        if ( ! $item['value'] || empty($item['value']))
        {
            $this->errors[$item['field']]['validation'] = SNB_IS_REQUIRED;
            $this->errors[$item['field']]['msg'] = 'This field is required';
        }
    }
    
    /**
     * Min length validation
     */
    private function min_length($item)
    {
        if ($item['value'] && strlen($item['value']) < $item['rule']['min_length'])
        {
            $this->errors[$item['field']]['validation'] = SNB_MIN_LENGTH;
            $this->errors[$item['field']]['msg'] = 'The field is too short (minimun is '.$item['rule']['min_length'].' characters)';
        }
    }
    
    /**
     * Max length validation
     */
    private function max_length($item)
    {    
        if ($item['value'] && strlen($item['value']) > $item['rule']['max_length'])
        {
            $this->errors[$item['field']]['validation'] = SNB_MAX_LENGTH;
            $this->errors[$item['field']]['msg'] = 'The field is too long (maximum is '.$item['rule']['max_length'].' characters)';
        }
    }
    
    /**
     * Is numeric validation
     */
    private function is_numeric($item)
    {
        if ($item['value'] && ! is_numeric($item['value']))
        {
            $this->errors[$item['field']]['validation'] = SNB_IS_NUMERIC;
            $this->errors[$item['field']]['msg'] = 'The field is not a number';
        }
    }
}
