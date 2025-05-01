<?php



namespace App\Utils;

class BadWordFilter
{
    public static function loadBadWords(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Fichier bad words introuvable");
        }

        $badWords = [];
        $file = fopen($filePath, 'r');
        
        while (($line = fgets($file)) !== false) {
            $badWords[] = trim(strtolower($line));
        }
        
        fclose($file);
        return $badWords;
    }

    public static function filterBadWords(string $text, array $badWords): string
    {
        if (empty($text)) {
            return $text;
        }

        $words = preg_split('/(\W+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        foreach ($words as &$word) {
            $cleanWord = preg_replace('/\W+/', '', $word);
            if (!empty($cleanWord) && in_array(strtolower($cleanWord), $badWords)) {
                $word = str_repeat('*', strlen($word));
            }
        }

        return implode('', $words);
    }
}