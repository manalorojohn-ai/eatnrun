<?php

function validate_name($name) {
    if (empty($name)) {
        return "Name is required";
    }
    
    // Remove extra spaces
    $name = trim(preg_replace('/\s+/', ' ', $name));
    
    // Check minimum length after trimming
    if (strlen($name) < 2) {
        return "Name must be at least 2 characters long";
    }
    
    // Check for valid characters (letters, spaces, and dots)
    if (!preg_match("/^[A-Za-z\s.]+$/", $name)) {
        return "Name can only contain letters, spaces, and dots";
    }
    
    // Check for at least one letter
    if (!preg_match("/[A-Za-z]/", $name)) {
        return "Name must contain at least one letter";
    }
    
    // Check for consecutive dots or spaces
    if (strpos($name, '..') !== false || strpos($name, '  ') !== false) {
        return "Name cannot contain consecutive dots or spaces";
    }
    
    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (substr($word, -1) === '.') {
            if (strlen($word) !== 2 || !ctype_upper($word[0])) {
                return "Initials must be a single capital letter followed by a dot";
            }
            continue;
        }
        
        if (strlen($word) < 2) {
            return "Each word must be at least 2 letters long (except initials)";
        }
        if (!ctype_upper($word[0])) {
            return "Each word must start with a capital letter";
        }
        if (!ctype_lower(substr($word, 1))) {
            return "Only the first letter of each word should be capitalized";
        }
        if (strlen($word) > 20) {
            return "Each word must be less than 20 characters";
        }
    }
    
    $common_patterns = ['asd', 'qwe', 'zxc', 'dfg', 'jkl', 'ghj'];
    $lower_name = strtolower($name);
    foreach ($common_patterns as $pattern) {
        if (strpos($lower_name, $pattern) !== false) {
            return "Please enter a valid name, not random letters";
        }
    }
    
    for ($i = 0; $i < strlen($name) - 2; $i++) {
        if (ctype_alpha($name[$i]) && $name[$i] === $name[$i + 1] && $name[$i] === $name[$i + 2]) {
            return "Name cannot contain three or more consecutive same letters";
        }
    }
    
    return null;
}

function validate_email($email) {
    if (empty($email)) {
        return "Email is required";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    if (preg_match('/[A-Z]/', $email)) {
        return "Email should contain only lowercase letters";
    }
    
    if (!preg_match('/\.com$/', $email)) {
        return "Email must end with .com";
    }
    
    return null;
}

function validate_contact_number($number) {
    if (empty($number)) {
        return "Contact number is required";
    }
    
    if (!preg_match("/^(09|\+639)\d{9}$/", $number)) {
        return "Contact number must be in format 09XXXXXXXXX or +639XXXXXXXXX";
    }
    
    return null;
}

?> 