<?php

namespace App\Support;

/** Maps free-text listing locations to Malaysian states so search can filter by state. */
class MalaysiaLocation
{
    /** Canonical state names with common city and spelling aliases. */
    public const STATES = [
        'Johor' => ['johor', 'johore', 'johor bahru', 'iskandar puteri', 'pasir gudang', 'kulai', 'batu pahat', 'muar', 'kluang', 'segamat', 'ulu tiram', 'masai', 'senai', 'skudai'],
        'Kedah' => ['kedah', 'alor setar', 'sungai petani', 'kulim', 'langkawi'],
        'Kelantan' => ['kelantan', 'kota bharu', 'machang', 'pasir mas', 'tanah merah', 'bukit bunga'],
        'Melaka' => ['melaka', 'malacca', 'ayer keroh', 'alor gajah', 'jasin'],
        'Negeri Sembilan' => ['negeri sembilan', 'seremban', 'nilai', 'port dickson'],
        'Pahang' => ['pahang', 'kuantan', 'temerloh', 'bentong', 'cameron highlands', 'genting'],
        'Perak' => ['perak', 'ipoh', 'taiping', 'teluk intan', 'seri iskandar', 'lumut', 'manjung', 'changkat jering', 'tekah', 'kampar', 'sitiawan'],
        'Perlis' => ['perlis', 'kangar', 'arau'],
        'Pulau Pinang' => ['pulau pinang', 'penang', 'george town', 'georgetown', 'bayan lepas', 'butterworth', 'bukit mertajam', 'perai', 'prai', 'batu kawan', 'gelugor', 'jelutong', 'simpang ampat', 'nibong tebal'],
        'Sabah' => ['sabah', 'kota kinabalu', 'sandakan', 'tawau', 'lahad datu', 'semporna', 'putatan', 'donggongon', 'penampang', 'keningau', 'kudat'],
        'Sarawak' => ['sarawak', 'kuching', 'miri', 'sibu', 'bintulu'],
        'Selangor' => ['selangor', 'petaling jaya', 'shah alam', 'subang', 'puchong', 'klang', 'cyberjaya', 'seri kembangan', 'serdang', 'kajang', 'bangi', 'semenyih', 'rawang', 'sungai buloh', 'ampang', 'balakong', 'banting', 'kapar', 'telok panglima garang', 'setia alam', 'damansara', 'kota damansara', 'sepang', 'bandar baru bangi', 'pandan indah', 'kepong, selangor', 'cheras, selangor', 'puncak alam'],
        'Terengganu' => ['terengganu', 'kuala terengganu', 'kemaman', 'dungun'],
        'Kuala Lumpur' => ['kuala lumpur', 'wp kuala lumpur', 'federal territory of kuala lumpur', 'kl city', 'kl eco city', 'bukit jalil', 'bukit bintang', 'setapak', 'cheras', 'kepong', 'bangsar', 'mont kiara', 'sri hartamas', 'wangsa maju', 'brickfields', 'kl sentral', 'damansara heights'],
        'Labuan' => ['labuan'],
        'Putrajaya' => ['putrajaya'],
    ];

    /** First two postcode digits => state, for listings that only publish a postcode. */
    private const POSTCODES = [
        'Perlis' => [1, 2], 'Kedah' => [5, 6, 8, 9], 'Pulau Pinang' => [10, 11, 12, 13, 14], 'Kelantan' => [15, 16, 17, 18],
        'Terengganu' => [20, 21, 22, 23, 24], 'Pahang' => [25, 26, 27, 28, 39, 49, 69], 'Perak' => [30, 31, 32, 33, 34, 35, 36],
        'Selangor' => [40, 41, 42, 43, 44, 45, 46, 47, 48, 63, 64, 68], 'Kuala Lumpur' => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60],
        'Putrajaya' => [62], 'Negeri Sembilan' => [70, 71, 72, 73], 'Melaka' => [75, 76, 77, 78], 'Johor' => [79, 80, 81, 82, 83, 84, 85, 86],
        'Labuan' => [87], 'Sabah' => [88, 89, 90, 91], 'Sarawak' => [93, 94, 95, 96, 97, 98],
    ];

    /** @return array<int, string> */
    public static function states(): array
    {
        return array_keys(self::STATES);
    }

    /** The state named earliest in the text, e.g. "Johor Bahru, 01, MY, Kuala Lumpur" => Johor. */
    public static function stateOf(?string $location): ?string
    {
        $text = mb_strtolower(trim($location ?? ''));
        if ($text === '') return null;
        $best = null;
        $position = PHP_INT_MAX;
        $length = 0;
        foreach (self::STATES as $state => $aliases) {
            foreach ([mb_strtolower($state), ...$aliases] as $alias) {
                // Earliest mention wins; at the same position the more specific (longer) alias wins.
                if (preg_match('/(?<![\pL])'.preg_quote($alias, '/').'(?![\pL])/u', $text, $match, PREG_OFFSET_CAPTURE)
                    && ($match[0][1] < $position || ($match[0][1] === $position && mb_strlen($alias) > $length))) {
                    [$best, $position, $length] = [$state, $match[0][1], mb_strlen($alias)];
                }
            }
        }
        if ($best) return $best;
        if (preg_match('/\b(\d{2})\d{3}\b/', $text, $postcode)) {
            foreach (self::POSTCODES as $state => $prefixes) if (in_array((int) $postcode[1], $prefixes, true)) return $state;
        }
        return null;
    }

    public static function isRemote(?string $location, ?string $workType = null, ?string $source = null): bool
    {
        return $source === 'remotive' || (bool) preg_match('/remote|worldwide|anywhere|global/i', ($location ?? '').' '.($workType ?? ''));
    }

    /** @param array<int, string> $values @return array<int, string> */
    public static function normalise(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($value) => self::stateOf($value), $values))));
    }

    /** A clean label for job cards, e.g. "Petaling Jaya, Selangor" or "Remote". */
    public static function label(?string $location, ?string $workType = null, ?string $source = null): string
    {
        if (self::isRemote($location, $workType, $source)) return 'Remote';
        $state = self::stateOf($location);
        if (!$state) return trim($location ?? '') ?: 'Malaysia';
        $city = trim(explode(',', $location ?? '')[0]);
        $usableCity = $city !== '' && !preg_match('/\d|^(malaysia|my)$/i', $city) && mb_strlen($city) <= 40 && self::stateOf($city) !== null && !str_contains(mb_strtolower($city), mb_strtolower($state));
        return $usableCity ? $city.', '.$state : $state;
    }
}
