<?php

class tools
{
        public function rule_stub($object, $field, $param = '')
        {
            $object->$field = strtolower(str_replace(" ", "_", $object->$field));
            $object->$field = preg_replace('/[^a-z0-9_]/i', '', $object->$field);
        }
		
		public function rule_checkbox($object, $field, $param = '')
        {
			if ($object->$field == 1) $object->$field = 1; else $object->$field = 0;
        }
        
        public function stub($input)
        {
			if(isset($input->stub)) $val = $input->stub;
			else if(isset($input->to_stub)) $val = $input->to_stub;
			else $val = $input->name;
            $val = strtolower(str_replace(" ", "_", $val));
            return preg_replace('/[^a-z0-9_]/i', '', $val);
        }

        public function logged_id()
        {
            $CI =& get_instance();
            return $CI->tank_auth->get_user_id();
        }
        
        public function rule_is_int($object, $field, $param = '')
        {
            if ($object->$field == "") return TRUE;
            $object->$field = (int) $object->$field;
            if(is_int($object->$field))
            {
                return TRUE;
            }
            else
            {
                $object->error_message('custom','The value '.$object->$field.' can\'t be put into '.$field.': not an integer');
            }
        }
}