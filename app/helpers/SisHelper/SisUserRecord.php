<?php

namespace App\Helpers;

use App\Exceptions\SisException;
use JsonSerializable;

/**
 * Wraper that parses and provides access to personal user data.
 */
class SisUserRecord implements JsonSerializable
{
    private string $ukco;

    private ?string $login = null;

    private string $titlesBeforeName = '';

    private string $firstName;

    private string $lastName;

    private string $titlesAfterName = '';

    private string $email;

    private bool $student = false;

    private bool $ucitel = false;

    private static function getOrThrow(array $data, string $key, bool $notEmpty = false)
    {
        if (!array_key_exists($key, $data)) {
            throw new SisException("Missing item '$key' in kdojekdo response.");
        }
        if (!$notEmpty && !$data[$key]) {
            throw new SisException("Missing item '$key' in kdojekdo response has empty value.");
        }
        return $data[$key];
    }

    /**
     * @param string $ukco
     * @param array $data
     * @return SisUserRecord
     * @throws SisException
     */
    public static function fromArray($ukco, $data): SisUserRecord
    {
        $result = new SisUserRecord();
        $result->ukco = self::getOrThrow($data, 'oidos', true);
        if ($result->ukco !== $ukco) {
            throw new SisException("The response from kdojekdo was for user $result->ukco, but $ukco was requested.");
        }

        $result->login = self::getOrThrow($data, 'login', true);
        $result->titlesBeforeName = self::getOrThrow($data, 'titul');
        $result->firstName = self::getOrThrow($data, 'jmeno', true);
        $result->lastName = self::getOrThrow($data, 'prijmeni', true);
        $result->titlesAfterName = self::getOrThrow($data, 'titulza');
        $result->email = self::getOrThrow($data, 'osobni_mail', true);

        $studia = $data['studia'] ?? [];
        foreach ($studia as $studium) {
            $result->student |= ($studium['sstav'] ?? '' === 'S');
        }

        $ucitel = $data['ucitel'] ?? [];
        foreach ($ucitel as $ucit) {
            $result->ucitel |= ($ucit['uaktivni'] ?? '' === 'T');
        }

        return $result;
    }

    public function getUkco(): string
    {
        return $this->ukco;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'ukco' => $this->ukco,
            'login' => $this->login,
            'titlesBeforeName' => $this->titlesBeforeName,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'titlesAfterName' => $this->titlesAfterName,
            'email' => $this->email,
            'student' => $this->student,
            'ucitel' => $this->ucitel,
        ];
    }
}
