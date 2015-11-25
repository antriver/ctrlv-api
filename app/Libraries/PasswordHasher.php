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
     * @param string $password
     * @param BaseModel $model
     * @param string $column
     *
     * @return bool
     */
    public function verify($password, BaseModel $model, $column)
    {
        $savedHash = $model->{$column};

        // If password is old format
        if (substr($savedHash, 0, 1) != '$') {
            if ($this->generateOldHash($password) == $savedHash) {
                $this->updatedSavedHash($password, $model, $column);
                return true;
            }

            // Incorrect password
            return false;
        }

        // Password is in the new PHP 5.5 format
        if (password_verify($password, $savedHash)) {
            if (password_needs_rehash($savedHash, $this->algorithm, ['cost' => $this->cost])) {
                $this->updatedSavedHash($password, $model, $column);
            }
            return true;
        }

        // Incorrect password
        return false;
    }

    /**
     * Generates an old md5 hash to validate a user's password if it has not been updated yet.
     *
     * @param string $password
     *
     * @return string
     */
    private function generateOldHash($password)
    {
        return md5($password);
    }

    /**
     * Generates a hash of the given string.
     *
     * @param string $password
     *
     * @return string
     */
    public function generateHash($password)
    {
        return password_hash($password, $this->algorithm, ['cost' => $this->cost]);
    }

    /**
     * Updates the stored password to a new hash if required.
     *
     * @param string $password
     * @param BaseModel $model
     * @param string $column
     */
    private function updatedSavedHash($password, BaseModel $model, $column)
    {
        // Generate a new hash
        $newHash = $this->generateHash($password);

        $model->{$column} = $newHash;
        $model->save();
    }
}
