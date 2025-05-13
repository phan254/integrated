<?php
// Prevent direct access or ensure BASE_URL is defined
if(!defined('BASE_URL')) {
    if(file_exists('config/db_config.php')) {
        require_once 'config/db_config.php';
        define('BASE_URL', 'http://localhost/IHMS/');
    } else {
        exit("Configuration file not found.");
    }
}

// Rest of the file code...

/**
 * Generate an input field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param string $type Input type (text, email, password, etc.)
 * @param array $attributes Additional attributes
 * @return string HTML input field
 */
function form_input($name, $label, $type = 'text', $attributes = []) {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $value = isset($attributes['value']) ? $attributes['value'] : '';
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    $placeholder = isset($attributes['placeholder']) ? $attributes['placeholder'] : '';
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-control';
    $error = isset($attributes['error']) ? $attributes['error'] : '';
    $help_text = isset($attributes['help_text']) ? $attributes['help_text'] : '';
    
    $is_invalid = !empty($error) ? 'is-invalid' : '';
    
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $id . '" class="form-label">' . $label;
    if($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= '</label>';
    $html .= '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" class="' . $class . ' ' . $is_invalid . '" value="' . $value . '" placeholder="' . $placeholder . '" ' . $required . '>';
    
    if(!empty($error)) {
        $html .= '<div class="invalid-feedback">' . $error . '</div>';
    }
    
    if(!empty($help_text)) {
        $html .= '<div class="form-text">' . $help_text . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a textarea field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param array $attributes Additional attributes
 * @return string HTML textarea field
 */
function form_textarea($name, $label, $attributes = []) {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $value = isset($attributes['value']) ? $attributes['value'] : '';
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    $placeholder = isset($attributes['placeholder']) ? $attributes['placeholder'] : '';
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-control';
    $rows = isset($attributes['rows']) ? $attributes['rows'] : '3';
    $error = isset($attributes['error']) ? $attributes['error'] : '';
    $help_text = isset($attributes['help_text']) ? $attributes['help_text'] : '';
    
    $is_invalid = !empty($error) ? 'is-invalid' : '';
    
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $id . '" class="form-label">' . $label;
    if($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= '</label>';
    $html .= '<textarea name="' . $name . '" id="' . $id . '" class="' . $class . ' ' . $is_invalid . '" rows="' . $rows . '" placeholder="' . $placeholder . '" ' . $required . '>' . $value . '</textarea>';
    
    if(!empty($error)) {
        $html .= '<div class="invalid-feedback">' . $error . '</div>';
    }
    
    if(!empty($help_text)) {
        $html .= '<div class="form-text">' . $help_text . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a select field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param array $options Select options
 * @param array $attributes Additional attributes
 * @return string HTML select field
 */
function form_select($name, $label, $options, $attributes = []) {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $selected = isset($attributes['selected']) ? $attributes['selected'] : '';
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-select';
    $error = isset($attributes['error']) ? $attributes['error'] : '';
    $help_text = isset($attributes['help_text']) ? $attributes['help_text'] : '';
    
    $is_invalid = !empty($error) ? 'is-invalid' : '';
    
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $id . '" class="form-label">' . $label;
    if($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= '</label>';
    $html .= '<select name="' . $name . '" id="' . $id . '" class="' . $class . ' ' . $is_invalid . '" ' . $required . '>';
    
    foreach($options as $value => $option_label) {
        $is_selected = ($value == $selected) ? 'selected' : '';
        $html .= '<option value="' . $value . '" ' . $is_selected . '>' . $option_label . '</option>';
    }
    
    $html .= '</select>';
    
    if(!empty($error)) {
        $html .= '<div class="invalid-feedback">' . $error . '</div>';
    }
    
    if(!empty($help_text)) {
        $html .= '<div class="form-text">' . $help_text . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a checkbox field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param array $attributes Additional attributes
 * @return string HTML checkbox field
 */
function form_checkbox($name, $label, $attributes = []) {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $checked = isset($attributes['checked']) && $attributes['checked'] ? 'checked' : '';
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-check-input';
    $value = isset($attributes['value']) ? $attributes['value'] : '1';
    
    $html = '<div class="form-check mb-3">';
    $html .= '<input type="checkbox" name="' . $name . '" id="' . $id . '" class="' . $class . '" value="' . $value . '" ' . $checked . '>';
    $html .= '<label class="form-check-label" for="' . $id . '">' . $label . '</label>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a submit button
 * 
 * @param string $label Button label
 * @param array $attributes Additional attributes
 * @return string HTML button
 */
function form_submit($label = 'Submit', $attributes = []) {
    $name = isset($attributes['name']) ? $attributes['name'] : 'submit';
    $class = isset($attributes['class']) ? $attributes['class'] : 'btn btn-primary';
    
    return '<button type="submit" name="' . $name . '" class="' . $class . '">' . $label . '</button>';
}