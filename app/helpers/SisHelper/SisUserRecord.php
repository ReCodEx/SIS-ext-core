<?php

namespace App\Helpers;

use App\Exceptions\SisException;
use App\Model\Entity\SisUser;
use JsonSerializable;

/**
 * Warper that parses and provides access to personal user data.
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

    private bool $teacher = false;

    private static function getOrThrow(array $data, string $key)
    {
        if (!array_key_exists($key, $data)) {
            throw new SisException("Missing item '$key' in kdojekdo response.");
        }
        if (!$data[$key]) {
            throw new SisException("Missing item '$key' in kdojekdo response has empty value.");
        }
        return $data[$key];
    }

    /**
     * Initialize the record using data returned from SIS API call.
     * @param string $ukco
     * @param array $data
     * @return SisUserRecord
     * @throws SisException
     */
    public static function fromArray($ukco, $data): SisUserRecord
    {
        $result = new SisUserRecord();
        $result->ukco = self::getOrThrow($data, 'oidos');
        if ($result->ukco !== $ukco) {
            throw new SisException("The response from kdojekdo was for user $result->ukco, but $ukco was requested.");
        }

        $result->login = mb_strtolower(self::getOrThrow($data, 'login'));
        $result->titlesBeforeName = $data['titul'] ?? '';
        $result->firstName = self::getOrThrow($data, 'jmeno');
        $result->lastName = self::getOrThrow($data, 'prijmeni');
        $result->titlesAfterName = $data['titulza'] ?? '';
        $result->email = self::getOrThrow($data, 'osobni_mail');

        $studia = $data['studia'] ?? [];
        foreach ($studia as $studium) {
            $sstav = $studium['sstav'] ?? '';
            $result->student = $result->student || $sstav === 'S' // is studying
                || $sstav === 'R' // decomposed year
                || $sstav === 'X' // accepted for studies
                || $sstav === 'O' // repeating
                || $sstav === 'D'; // proceeding to termination (but still studying)

            if ($result->student) {
                break; // no need to continue (perf. optimization)
            }
        }

        $ucitel = $data['ucitel'] ?? [];
        foreach ($ucitel as $ucit) {
            $result->teacher = $result->teacher || ($ucit['uaktivni'] ?? '') === 'T';
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
            'teacher' => $this->teacher,
        ];
    }

    /**
     * Create new SisUser entity and fill it with data.
     * @return SisUser (not persisted)
     */
    public function createUser(): SisUser
    {
        return new SisUser(
            $this->ukco,
            $this->login,
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->titlesBeforeName,
            $this->titlesAfterName,
            $this->student,
            $this->teacher
        );
    }

    /**
     * Update properties of given user to match data in this record.
     * @param SisUser $user to be updated
     * @return bool true if at least one property was changed
     */
    public function updateUser(SisUser $user): bool
    {
        if ($user->getId() !== $this->ukco) {
            throw new SisException("User ID mismatch.");
        }

        $props = [
            'login',
            'email',
            'firstName',
            'lastName',
            'titlesBeforeName',
            'titlesAfterName',
            'student',
            'teacher'
        ];
        $changed = false;
        foreach ($props as $prop) {
            $getter = 'get' . ucfirst($prop);
            $setter = 'set' . ucfirst($prop);
            if ($user->$getter() !== $this->$prop) {
                $user->$setter($this->$prop);
                $changed = true;
            }
        }

        return $changed;
    }
}
