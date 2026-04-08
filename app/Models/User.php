<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'show_id',
        'name',
        'email',
        'password',
        'two_factor_method',
        'two_factor_secret',
        'role',
        'hgs12_user',
        'sign_up_at',
        'email_verification_token',
        'email_verification_sent_at',
        'withdrawn_at',
        'privacy_policy_accepted_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sign_up_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_secret' => 'encrypted',
        ];
    }

    /**
     * お気に入りゲームタイトルを取得
     */
    public function favoriteGameTitles(): BelongsToMany
    {
        return $this->belongsToMany(GameTitle::class, UserFavoriteGameTitle::class, 'user_id', 'game_title_id');
    }

    /**
     * 怖さメーター評価を取得
     */
    public function fearMeters(): HasMany
    {
        return $this->hasMany(UserGameTitleFearMeter::class, 'user_id');
    }

    /**
     * 怖さメーター入力制限
     */
    public function fearMeterRestrictions(): HasMany
    {
        return $this->hasMany(UserFearMeterRestriction::class, 'user_id');
    }

    /**
     * ソーシャルアカウント連携を取得
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * OAuthのみで登録し、まだパスワードを設定していないか
     */
    public function needsPasswordSet(): bool
    {
        return $this->password === null && $this->socialAccounts()->exists();
    }

    /**
     * メール2段階認証が有効か
     */
    public function hasTwoFactorEmail(): bool
    {
        return $this->two_factor_method === 'email';
    }

    /**
     * TOTP（Authenticator）2段階認証が有効か
     */
    public function hasTwoFactorTotp(): bool
    {
        return $this->two_factor_method === 'totp';
    }

    /**
     * 2段階認証が有効か（将来の認証方式追加に備えたラッパー）
     */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_method !== null;
    }
}
