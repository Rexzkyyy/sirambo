<?php

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Arr;

class BpsProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile-pegawai'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/auth/realms/'.$this->getRealm().'/protocol/openid-connect/auth', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/auth/realms/'.$this->getRealm().'/protocol/openid-connect/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/auth/realms/'.$this->getRealm().'/protocol/openid-connect/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array  $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'username' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email') ?? Arr::get($user, 'preferred_username') . '@bps.go.id',
            'nip' => Arr::get($user, 'nip'),
            'nama_depan' => Arr::get($user, 'given_name'),
            'nama_belakang' => Arr::get($user, 'family_name'),
            // Tambahan field organisasi jika tersedia di profile-pegawai
            'kode_provinsi' => Arr::get($user, 'kode_provinsi'),
            'kode_kabupaten' => Arr::get($user, 'kode_kabupaten'),
            'kode_organisasi' => Arr::get($user, 'kode_organisasi'),
        ]);
    }

    protected function getBaseUrl()
    {
        return config('services.bps.base_url');
    }

    protected function getRealm()
    {
        return config('services.bps.realm');
    }
}
