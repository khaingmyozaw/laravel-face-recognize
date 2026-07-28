<?php

namespace App\Models\Concerns;

use App\Models\PersonalAccessToken;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait HasApiTokens
{
    public ?PersonalAccessToken $currentAccessToken = null;

    /**
     * @return MorphMany<PersonalAccessToken, $this>
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * @param  list<string>  $abilities
     * @return array{accessToken: PersonalAccessToken, plainTextToken: string}
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): array
    {
        $plainTextToken = sprintf('%s%s', $this->id, Str::random(40));

        $accessToken = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'accessToken' => $accessToken,
            'plainTextToken' => $plainTextToken,
        ];
    }

    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->currentAccessToken;
    }

    public function withAccessToken(PersonalAccessToken $accessToken): static
    {
        $this->currentAccessToken = $accessToken;

        return $this;
    }
}
