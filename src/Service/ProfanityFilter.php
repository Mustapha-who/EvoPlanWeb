<?php

namespace App\Service;

class ProfanityFilter
{
    private array $badWords = [
        'merdique' => '*******',
        'nul' => '***',
        'catastrophe' => '***********',
        'idiot' => '*****',
        'stupide' => '*******',
    ];

    public function censor(string $text): string
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        foreach ($this->badWords as $badWord => $replacement) {
            $pattern = '/\b' . preg_quote($badWord, '/') . '\b/i';
            $textLower = preg_replace($pattern, $replacement, $textLower);
        }

        // Restaurer la casse originale pour les parties non censurées
        $result = '';
        for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
            $originalChar = mb_substr($text, $i, 1, 'UTF-8');
            $lowerChar = mb_substr($textLower, $i, 1, 'UTF-8');
            $result .= ($lowerChar === mb_strtolower($originalChar, 'UTF-8')) ? $originalChar : $lowerChar;
        }

        return $result;
    }
}