<?php

namespace App\Service\UserModule;

class PasswordEncryption
{
    /**
     * Hashes a password using PHP's built-in password_hash function.
     *
     * @param string $password The plain password.
     * @return string The hashed password.
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifies a password against a hash using PHP's built-in password_verify function.
     *
     * @param string $password The plain password.
     * @param string $hashedPassword The hashed password.
     * @return bool True if the password is correct, false otherwise.
     */
    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Checks if a hashed password needs rehashing using PHP's built-in password_needs_rehash function.
     *
     * @param string $hashedPassword The hashed password.
     * @return bool True if the password needs rehashing, false otherwise.
     */
    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT);
    }



}