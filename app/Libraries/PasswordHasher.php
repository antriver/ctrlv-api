<?php

namespace CtrlV\Libraries;

use CtrlV\Models\Base\BaseModel;

/**
 * Class PasswordHasher
 * There are passwords stored in a few different places that we want upgrading, so this takes care
 * of all password hashing things so they don't have to.
 */
class PasswordHasher
{
    private $algorithm = PASSWORD_DEFAULT;
    private $cost = 10;

    /**
     * Check if a password is correct.
     *
     * @param string $string
     * @param BaseModel $model
     * @param string $column
     * @param string $oldFormat What format was the saved value in the old version?
     *
     * @return bool
     */
    public function verify(
        $string,
        BaseModel $model,
        $column,
        $oldFormat = 'md5'
    ) {
        $savedHash = $model->{$column};

        // If password is old format
        if (substr($savedHash, 0, 1) !== '$') {
            if ($this->generateOldHash($string, $oldFormat) == $savedHash) {
                $this->updatedSavedHash($string, $model, $column);

                return true;
            }

            // Incorrect password
            return false;
        }

        // Password is in the new format
        if (password_verify($string, $savedHash)) {
            if (password_needs_rehash($savedHash, $this->algorithm, ['cost' => $this->cost])) {
                $this->updatedSavedHash($string, $model, $column);
            }

            return true;
        }

        // Incorrect password
        return false;
    }

    /**
     * Generates an old hash to validate a password if it has not been updated yet.
     *
     * @param string $string
     * @param string $oldFormat
     *
     * @return string
     */
    private function generateOldHash($string, $oldFormat = 'md5')
    {
        switch ($oldFormat) {
            case 'md5':
                return md5($string);
                break;

            case 'plain':
            default:
                return $string;
                break;
        }
    }

    /**
     * Generates a hash of the given string.
     *
     * @param string $string
     *
     * @return string
     */
    public function generateHash($string, array $options = [])
    {
        if (!isset($options['cost'])) {
            $options['cost'] = $this->cost;
        }

        return password_hash($string, $this->algorithm, $options);
    }

    /**
     * Updates the stored password to a new hash if required.
     *
     * @param string $string
     * @param BaseModel $model
     * @param string $column
     */
    private function updatedSavedHash($string, BaseModel $model, $column)
    {
        // Generate a new hash
        $newHash = $this->generateHash($string);

        $model->{$column} = $newHash;
        $model->save();
    }

    /**
     * Generates a pseudo-random string (to become session keys and image keys).
     *
     * @return string
     */
    public function generateKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}
