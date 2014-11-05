<?php

/**
 * Class Validation
 */
class Avalidator
{
    /**
     * @var array
     */
    private $_error_messages = array();
    /**
     * @var array
     */
    private $_errors = array();
    /**
     * @var array
     */
    private $_error_container = array(
        'start' => '<div class="error">',
        'end' => '</div>');
    /**
     * @var array
     */
    protected $_fields = array();

    /**
     * @param $start
     * @param $end
     */
    public function set_error_container($start, $end)
    {
        $this->_error_container['start'] = $start;
        $this->_error_container['end'] = $end;
    }

    /**
     * @param $variable_name
     * @param $type_val
     * @param $rules
     * @param string $element_name
     */
    public function field($variable_name, $type_val, $rules, $element_name = '')
    {
        if (empty($element_name)) $element_name = $variable_name;
        if ($type_val == '_GET' && isset($_GET[$variable_name])) {
            $get_value = $_GET[$variable_name];
            unset($_GET[$variable_name]);
        } elseif ($type_val == '_POST' && isset($_POST[$variable_name])) {
            $get_value = $_POST[$variable_name];
            unset($_POST[$variable_name]);
        } elseif (!empty($type_val) && $type_val != '_GET' && $type_val != '_POST') {
            $get_value = $type_val;
            unset($type_val);
        } else {
            $get_value = '';
        }
        $get_rules = $this->_get_rules($rules);
        is_array($get_value) ? $value = $get_value : $value[] = $get_value;
        $this->_fields[$variable_name] = array('element_name' => $element_name, 'value' => $value, 'rules' => $get_rules);
    }

    /**
     *
     * @param string $rules
     * @return array $rules
     */
    private function _get_rules($rules)
    {
        $rules_arr = explode('|', $rules);
        $return_rules = array();
        foreach ($rules_arr as $rule) {
            $args = '';
            if (strpos($rule, '[')) {
                $args = substr($rule, strpos($rule, '['), strpos($rule, ']'));
                $rule = str_replace($args, '', $rule);
                $args = str_replace(array('[', ']'), '', $args);
            }
            $custom_message = '';
            if (strpos($rule, '<*')) {
                $custom_message = substr($rule, strpos($rule, '<*'), strpos($rule, '*>'));
                $rule = str_replace($custom_message, '', $rule);
                $custom_message = str_replace(array('<*', '*>'), '', $custom_message);
            }
            $return_rules[$rule] = array('args' => $args, 'custom_message' => $custom_message);
        }
        return $return_rules;
    }

    /**
     *
     */
    public function validate()
    {
        if (empty($this->_fields)) {
            echo 'Nothing to validate!';
            return FALSE;
        }
        foreach ($this->_fields as $key => $field) {
            foreach ($field['rules'] as $rule => $params) {
                if (!isset($this->_errors[$key]['required'])) {
                    if (method_exists($this, '_validate_' . $rule)) {
                        foreach ($this->_fields[$key]['value'] as $subkey => $value) {
                            $this->{'_validate_' . $rule}($key, $subkey, $params);
                        }
                    } else {
                        echo 'Validation rule ' . $rule . ' does not exist.';
                    }
                }
            }
        }
        if (empty($this->_errors)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $key
     * @param bool $escaped
     * @return mixed
     */
    public function get_value($key, $escaped = TRUE, $return_type = 'single')
    {
        if (!empty($this->_fields) && isset($this->_fields[$key]['value'])) {
            $value = $this->_fields[$key]['value'];
            $rules = array_keys($this->_fields[$key]['rules']);
        }
        if (isset($value)) {
            if ($escaped) {
                foreach ($value as $key => $escape_value) {
                    if (in_array('email', $rules)) {
                        $value[$key] = filter_var($escape_value, FILTER_SANITIZE_EMAIL);
                    } elseif (in_array('float', $rules)) {
                        $value[$key] = filter_var($escape_value, FILTER_SANITIZE_NUMBER_FLOAT);
                    } elseif (in_array('integer', $rules)) {
                        $value[$key] = filter_var($escape_value, FILTER_SANITIZE_NUMBER_INT);
                    } elseif (in_array('url', $rules)) {
                        $value[$key] = filter_var($escape_value, FILTER_SANITIZE_URL);
                    } else {
                        $value[$key] = filter_var($escape_value, FILTER_SANITIZE_STRING);
                    }
                }
            }
            switch ($return_type) {
                case 'single':
                    return $value[0];
                    break;

                case 'array':
                default:
                    return $value;
                    break;
            }
        } else {
            switch ($return_type) {
                case 'single':
                    return '';
                    break;

                case 'array':
                default:
                    return array();
                    break;
            }
        }
    }

    /**
     * @param null $key
     * @param null $container_start
     * @param null $container_end
     * @return bool|string
     */
    public function errors($key = NULL, $container_start = NULL, $container_end = NULL)
    {
        if (isset($container_start) && isset($container_end)) {
            set_error_container($container_start, $container_end);
        }
        $errors = array();
        if (!isset($key)) {
            foreach ($this->_errors as $variable) {
                foreach ($variable as $error) {
                    $errors[] = $error;
                }
            }
        } else {
            if (array_key_exists($key, $this->_errors)) {
                $errors = $this->_errors[$key];
            }
        }
        if (isset($errors)) {
            $returned = '';
            foreach ($errors as $error) {
                $returned .= $this->_error_container['start'];
                $returned .= $error;
                $returned .= $this->_error_container['end'];
            }
            return $returned;
        } else {
            return true;
        }
    }

    /**
     * @param $key
     * @param $error
     */
    private function _write_error($key, $rule_name, $params)
    {
        if (isset($this->_fields[$key]['rules'][$rule_name]['custom_message']) && !empty($this->_fields[$key]['rules'][$rule_name]['custom_message'])) {
            $error_message = $this->_fields[$key]['rules'][$rule_name]['custom_message'];
        } else {
            $error_message = vsprintf($this->_error_messages[$rule_name], $params);
        }
        $this->_errors[$key][$rule_name] = $error_message;
    }

    /**
     * @param $key
     * @param $error
     */
    public function append_error($key, $error_id = 'appended', $error_mess = 'There was another error.')
    {
        $this->_errors[$key][$error_id] = $error_mess;
    }

    /**
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_trim($key, $subkey, $params)
    {
        if (strlen($params['args']) == 0) {
            $params['args'] = " \t\n\r\0\x0B";
        }
        $this->_fields[$key]['value'][$subkey] = trim($this->_fields[$key]['value'][$subkey], $params['args']);
        return true;
    }

    /**
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_required($key, $subkey, $params)
    {
        $this->_error_messages['required'] = 'Campul %s este obligatoriu';
        if (strlen($this->_fields[$key]['value'][$subkey]) == 0) {
            $this->_write_error($key, 'required', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_length($key, $subkey, $params)
    {
        $args = $params['args'];
        if (strlen($args) == 0) $args = '0';
        $this->_error_messages['str_between'] = 'Field %s must containe between %s and %s characters';
        $this->_error_messages['str_morethan'] = 'Field %s must contain more than %s characters';
        $this->_error_messages['str_exact'] = 'Field %s must contain lesser than %s characters';
        if (strpos($args, '-') !== FALSE) {
            $type = 'between';
            $args_arr = explode('-', $this->_fields[$key]['rules']['length']);
            if (empty($args_arr[0])) {
                $args_arr[0] = '0';
            }
        } elseif (substr($this->_fields[$key]['rules']['length'], -1) == '+') {
            $value = intval(rtrim($this->_fields[$key]['rules']['length'], '+'));
            $type = 'morethan';
        } else {
            $value = intval($this->_fields[$key]['rules']['length']);
            $type = 'exact';
        }
        $element_value_size = strlen($this->_fields[$key]['value'][$subkey]);
        switch ($type) {
            // if the value<required value or value>required value then error
            case 'between':
                if ($element_value_size < $args_arr[0] || $element_value_size > $args_arr[1]) {
                    $this->_write_error($key, 'str_between', array($this->_fields[$key]['element_name'], $args_arr[0], $args_arr[1]));
                }
                break;
            // if value<=required value then error
            case 'morethan':
                if ($element_value_size <= $value) {
                    $this->_write_error($key, 'str_morethan', array($this->_fields[$key]['element_name'], $value));
                }
                break;
            // if value does not equal required value then error
            case 'exact':
                if ($element_value_size != $value) {
                    $this->_write_error($key, 'str_exact', array($this->_fields[$key]['element_name'], $value));
                }
                break;
        }
        return true;
    }


    /**
     * _validate_alpha ($key,$subkey,$params)
     * Validates alphabetical characters, also allowing the characters that are mentioned in $args
     *
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_alpha($key, $subkey, $params)
    {
        $this->_error_messages['alpha'] = 'Field %s must contain only alphabetical characters';
        $allowed_chars = array();
        if (strlen($params['args']) > 0) {
            $allowed_chars = str_split($params['args']);
        }
        $validated = true;
        $value = str_split($this->_fields[$key]['value'][$subkey]);
        foreach ($value as $char) {
            if (!ctype_alpha($char) && !in_array($char, $allowed_chars)) {
                $validated = false;
            }
        }
        if ($validated === false) {
            $this->_write_error($key, 'alpha', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * _validate_alphanumeric ($key,$subkey,$params)
     * Validates alphanumeric characters, also allowing the characters that are mentioned in $args
     *
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_alphanumeric($key, $subkey, $params)
    {
        $this->_error_messages['alphanumeric'] = 'Field %s must contain only alphanumerical characters';
        $allowed_chars = array();
        if (strlen($params['args']) > 0) {
            $allowed_chars = str_split($params['args']);
        }
        $validated = true;
        $value = str_split($this->_fields[$key]['value'][$subkey]);
        foreach ($value as $char) {
            if (!ctype_alnum($char) && !in_array($char, $allowed_chars)) {
                $validated = false;
            }
        }
        if ($validated === false) {
            $this->_write_error($key, 'alphanumeric', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * _validate_numeric ($key,$subkey,$params)
     * Validates numeric characters
     *
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_numeric($key, $subkey, $params)
    {
        $this->_error_messages['numeric'] = 'Field %s must contain only numerical characters';
        $allowed_chars = array();
        if (strlen($params['args']) > 0) {
            $allowed_chars = str_split($params['args']);
        }
        $validated = true;
        $value = str_split($this->_fields[$key]['value'][$subkey]);
        foreach ($value as $char) {
            if (!ctype_digit($char) && !in_array($char, $allowed_chars)) {
                $validated = false;
            }
        }
        if ($validated === false) {
            $this->_write_error($key, 'numeric', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * Validate integers, whether we talk about negative or positive numbers. Also, if arguments provided, it verifies if the value is lesser, equal, or bigger than limits
     * @param $key
     * @param $subkey
     * @return bool
     */
    private function _validate_integer($key, $subkey, $params)
    {
        $this->_error_messages['integer'] = 'Field %s must containe only integers';
        if (!filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_INT)) {
            $this->_write_error($key, 'integer', array($this->_fields[$key]['element_name']));
        }

        if (strlen($params['args']) > 0) {
            $this->_compare_values($key, $subkey, $params['args']);
        }
        return true;
    }

    /**
     * Validate floats, whether we talk about negative or positive numbers. Also, if arguments provided, it verifies if the value is lesser, equal, or bigger than limits
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_float($key, $subkey, $params)
    {
        $this->_error_messages['float'] = 'Field %s must contain a float number';
        if (!filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_FLOAT)) {
            $this->_write_error($key, 'float', array($this->_fields[$key]['element_name']));
        }

        if (strlen($params['args']) > 0) {
            $this->_compare_values($key, $subkey, $params['args']);
        }
        return true;
    }

    /**
     * Compare values to assert if one value is bigger, equal or smaller than another value.
     * @param type $key
     * @param type $subkey
     * @param type $args
     * @return boolean
     */
    private function _compare_values($key, $subkey, $args)
    {
        $this->_error_messages['not_lesser_than'] = 'Field %s must have a value lower than %s';
        $this->_error_messages['not_lesser_equal_than'] = 'Field %s must containe a value lower or equal with %s';
        $this->_error_messages['not_equal_with'] = 'Field %s must contain a value equal with %s';
        $this->_error_messages['not_bigger_equal_than'] = 'Field %s must contain a value bigger or equal with %s';
        $this->_error_messages['not_bigger_than'] = 'Field %s must contain a value bigger than %s';
        $args_arr = explode('&', $args);
        $value = $this->_fields[$key]['value'][$subkey];
        foreach ($args_arr as $arg) {
            preg_match_all('/^(<|<=|=|>=|>)([0-9]+)(\.([0-9]+))*/', $arg, $matches);
            $to_compare_with = $matches[2][0];
            $operator = $matches[1][0];
            if (($operator == '<') && !($value < $to_compare_with)) {
                $this->_write_error($key, 'not_lesser_than', array($this->_fields[$key]['element_name'], $to_compare_with));
            }
            if (($operator == '<=') && !($value <= $to_compare_with)) {
                $this->_write_error($key, 'not_lesser_equal_than', array($this->_fields[$key]['element_name'], $to_compare_with));
            }
            if (($operator == '=') && ($value != $to_compare_with)) {
                $this->_write_error($key, 'not_equal_with', array($this->_fields[$key]['element_name'], $to_compare_with));
            }
            if (($operator == '>=') && !($value >= $to_compare_with)) {
                $this->_write_error($key, 'not_bigger_equal_than', array($this->_fields[$key]['element_name'], $to_compare_with));
            }
            if (($operator == '>') && !($value > $to_compare_with)) {
                $this->_write_error($key, 'not_bigger_than', array($this->_fields[$key]['element_name'], $to_compare_with));
            }
        }
        return true;
    }

    /**
     * Validate an email address.
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_email($key, $subkey, $params)
    {
        $this->_error_messages['email'] = 'Field %s must contain a valid email address';
        if (filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_EMAIL) === false) {
            $this->_write_error($key, 'email', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * Validate an URL.
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_url($key, $subkey, $params)
    {
        $this->_error_messages['url'] = 'Field %s must contain a valid URL address';
        if (filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_URL)) {
            $this->_write_error($key, 'url', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * Validate an ip IP address.
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_ip($key, $subkey, $params)
    {
        $this->_error_messages['ip'] = 'Field %s must contain a valid IP address';
        if ((filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) && (filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false)) {
            $this->_write_error($key, 'ip', array($this->_fields[$key]['element_name']));
        }
        return true;
    }

    /**
     * Validate a boolean.
     * @param $key
     * @param $subkey
     * @param $params
     * @return bool
     */
    private function _validate_boolean($key, $subkey, $params)
    {
        $this->_error_messages['boolean'] = 'Field %s must be a boolean value (1,0,true,false)';
        if (filter_var($this->_fields[$key]['value'][$subkey], FILTER_VALIDATE_BOOLEAN)) {
            $this->_write_error($key, 'boolean', array($this->_fields[$key]['element_name']));
        }
        return true;
    }
}