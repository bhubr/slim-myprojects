<?php
namespace bhubr\MyProjects\Service;

use Respect\Validation\Validator as v;
use Sabre\Event\EventEmitter;

class AuthService {

    protected $emitter;

    public function __construct(EventEmitter $emitter) {
        $this->emitter = $emitter;
    }

    public function validateUser($attributes) {
        $errors = [];

        if( !array_key_exists('firstname', $attributes) || empty( $attributes['firstname'] ) ) {
            $errors[] = "First name is empty";
        }
        if( !array_key_exists('lastname', $attributes) || empty( $attributes['lastname'] ) ) {
            $errors[] = "Last name is empty";
        }
        if( !array_key_exists('email', $attributes) || empty( $attributes['email'] ) ) {
            $errors[] = "Email is empty";
        }
        else if( !v::email()->validate( $attributes['email'] ) ) {
            $errors[] = "Provided email is invalid";
        }
        if( !array_key_exists('password', $attributes) || !v::stringType()->length(4, null)->validate( $attributes['password'] ) ) {
            $errors[] = "Provided password is too short (should be 4 characters minimum)";
        }
        if( !array_key_exists('password_confirmation', $attributes) || empty( $attributes['password_confirmation'] ) ) {
            $errors[] = "Password confirmation is empty";
        }
        if ( array_key_exists('password', $attributes) &&
             array_key_exists('password_confirmation', $attributes) &&
             $attributes['password'] != $attributes['password_confirmation'] ) {
            $errors[] = "Password confirmation mismatch";
        }
        
        return $errors;
    }
}