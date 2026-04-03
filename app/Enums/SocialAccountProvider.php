<?php

namespace App\Enums;

enum SocialAccountProvider: int
{
    case GitHub = 1;
    case Google = 2;
    case Facebook = 3;
    case X = 4;
    case Yahoo = 5;
    case Steam = 6;

    public function label(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::X => 'X',
            self::Yahoo => 'Yahoo',
            self::Steam => 'Steam',
        };
    }
}
