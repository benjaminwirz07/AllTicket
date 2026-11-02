<?php

const NEWS_CATEGORIES = [
    'AllTicket',
    'Música',
    'Festivales',
    'Consejos',
];

function newsSlug(string $title, string $id): string
{
    $title = strtr(mb_strtolower(trim($title), 'UTF-8'), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
    ]);
    $base = preg_replace('/[^a-z0-9]+/', '-', $title);
    $base = trim(is_string($base) ? $base : '', '-');
    $base = $base !== '' ? mb_substr($base, 0, 220, 'UTF-8') : 'noticia';

    return $base . '-' . str_replace('-', '', $id);
}

function newsReadingMinutes(string $content): int
{
    $text = trim(strip_tags($content));
    if ($text === '') {
        return 1;
    }

    preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
    return max(1, (int) ceil(count($matches[0] ?? []) / 200));
}

function newsSummary(string $summary, string $content, int $limit = 500): string
{
    $text = trim($summary);
    if ($text === '') {
        $text = trim(strip_tags($content));
    }

    return mb_strimwidth($text, 0, $limit, '…', 'UTF-8');
}
