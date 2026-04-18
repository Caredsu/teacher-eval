<?php
/**
 * Exception Classes
 * Custom exceptions for better error handling
 */

namespace App\Exceptions;

class ValidationException extends \Exception {}
class AuthException extends \Exception {}
class NotFoundException extends \Exception {}
class UnauthorizedException extends \Exception {}
class ForbiddenException extends \Exception {}
class BadRequestException extends \Exception {}
?>
