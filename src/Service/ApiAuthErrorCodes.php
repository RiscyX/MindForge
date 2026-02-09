<?php
declare(strict_types=1);

namespace App\Service;

final class ApiAuthErrorCodes
{
    public const INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    public const USER_INACTIVE = 'AUTH_USER_INACTIVE';
    public const USER_BLOCKED = 'AUTH_USER_BLOCKED';
    public const TOKEN_INVALID = 'AUTH_TOKEN_INVALID';
    public const TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    public const TOKEN_REVOKED = 'AUTH_TOKEN_REVOKED';
    public const TOKEN_REUSED = 'AUTH_TOKEN_REUSED';
    public const RATE_LIMITED = 'AUTH_RATE_LIMITED';
    public const FORBIDDEN_ROLE = 'AUTH_FORBIDDEN_ROLE';
    public const PASSWORD_MISMATCH = 'AUTH_PASSWORD_MISMATCH';
    public const EMAIL_ALREADY_USED = 'AUTH_EMAIL_ALREADY_USED';
    public const REGISTRATION_FAILED = 'AUTH_REGISTRATION_FAILED';
    public const PROFILE_UPDATE_FAILED = 'AUTH_PROFILE_UPDATE_FAILED';
    public const INVALID_AVATAR_FORMAT = 'AUTH_INVALID_AVATAR_FORMAT';
}
