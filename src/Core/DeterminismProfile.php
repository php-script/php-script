<?php

declare(strict_types=1);

namespace PhpScript\Core;

/**
 * Decides which PHP functions may be allowed in deterministic mode.
 *
 * Deterministic mode uses an allowlist: only functions whose result depends on
 * nothing but their arguments are accepted. Everything else (time, randomness,
 * the environment, locale-aware formatting, object ids, …) is refused unless the
 * host explicitly opts in via Engine::allowNonDeterministic().
 *
 * "Same input, same output" assumes the same PHP version and ini settings
 * (e.g. `precision`, `serialize_precision`) on every run.
 */
final class DeterminismProfile
{
    /**
     * Pure functions: same arguments, same result.
     */
    public const array PURE_FUNCTIONS = [
        // strings
        'strlen', 'mb_strlen', 'strtolower', 'strtoupper', 'mb_strtolower', 'mb_strtoupper',
        'ucfirst', 'lcfirst', 'ucwords', 'trim', 'ltrim', 'rtrim', 'str_pad', 'mb_str_pad', 'str_repeat',
        'str_replace', 'str_ireplace', 'substr', 'mb_substr', 'substr_count', 'mb_substr_count',
        'strpos', 'stripos', 'strrpos', 'mb_strpos', 'mb_stripos', 'mb_strrpos', 'str_contains',
        'str_starts_with', 'str_ends_with', 'strstr', 'stristr', 'strrchr', 'strrev', 'str_split',
        'mb_str_split', 'explode', 'implode', 'join', 'nl2br', 'wordwrap', 'chunk_split', 'strtr',
        'substr_replace', 'similar_text', 'levenshtein', 'soundex', 'metaphone', 'strcmp', 'strcasecmp',
        'strncmp', 'strncasecmp', 'strnatcmp', 'strnatcasecmp', 'htmlspecialchars', 'htmlspecialchars_decode',
        'html_entity_decode', 'htmlentities', 'strip_tags', 'addslashes', 'stripslashes',
        'number_format', 'ord', 'chr', 'bin2hex', 'hex2bin', 'base64_encode', 'base64_decode',
        'urlencode', 'urldecode', 'rawurlencode', 'rawurldecode', 'http_build_query', 'quotemeta',
        // math
        'abs', 'ceil', 'floor', 'round', 'intdiv', 'fmod', 'fdiv', 'max', 'min', 'pow', 'sqrt',
        'exp', 'log', 'log10', 'log2', 'pi', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'atan2',
        'deg2rad', 'rad2deg', 'hypot', 'is_nan', 'is_finite', 'is_infinite', 'base_convert',
        'bindec', 'decbin', 'hexdec', 'dechex', 'octdec', 'decoct',
        // types
        'intval', 'floatval', 'boolval', 'strval', 'is_int', 'is_integer', 'is_float', 'is_string',
        'is_bool', 'is_array', 'is_numeric', 'is_null', 'is_scalar', 'is_iterable', 'is_countable',
        'gettype', 'get_debug_type', 'ctype_digit', 'ctype_alpha', 'ctype_alnum', 'ctype_upper',
        'ctype_lower', 'ctype_space', 'ctype_punct', 'ctype_xdigit',
        // arrays
        'count', 'sizeof', 'in_array', 'array_keys', 'array_values', 'array_merge', 'array_merge_recursive',
        'array_combine', 'array_flip', 'array_slice', 'array_splice', 'array_reverse', 'array_sum',
        'array_product', 'array_unique', 'array_count_values', 'array_fill', 'array_fill_keys',
        'array_pad', 'array_chunk', 'array_column', 'array_key_exists', 'key_exists', 'array_key_first',
        'array_key_last', 'array_search', 'array_diff', 'array_diff_key', 'array_diff_assoc',
        'array_intersect', 'array_intersect_key', 'array_intersect_assoc', 'array_is_list', 'range',
        'array_map', 'array_filter', 'array_reduce', 'usort', 'uasort', 'uksort', 'sort', 'rsort',
        'asort', 'arsort', 'ksort', 'krsort', 'array_find', 'array_find_key', 'array_any', 'array_all',
        // hashing and encoding
        'md5', 'sha1', 'crc32', 'hash', 'hash_hmac', 'json_encode', 'json_decode', 'json_validate',
        // regular expressions
        'preg_match', 'preg_match_all', 'preg_replace', 'preg_split', 'preg_quote', 'preg_grep',
    ];

    /**
     * Well-known functions that are not deterministic. Kept for reference; the
     * check itself uses PURE_FUNCTIONS.
     */
    public const array NON_DETERMINISTIC_FUNCTIONS = [
        // time
        'time', 'microtime', 'hrtime', 'gettimeofday', 'date', 'gmdate', 'idate', 'getdate',
        'localtime', 'mktime', 'gmmktime', 'strtotime', 'strftime', 'gmstrftime',
        'date_create', 'date_create_immutable', 'date_default_timezone_get',
        // randomness
        'rand', 'mt_rand', 'random_int', 'random_bytes', 'lcg_value', 'uniqid',
        'shuffle', 'str_shuffle', 'array_rand', 'openssl_random_pseudo_bytes',
        // environment
        'getenv', 'gethostname', 'getmypid', 'getmyuid', 'getmygid', 'getmyinode', 'php_uname',
        'sys_getloadavg', 'memory_get_usage', 'memory_get_peak_usage', 'spl_object_id', 'spl_object_hash',
    ];

    public static function isNonDeterministic(string $function): bool
    {
        return ! in_array(self::normalize($function), self::PURE_FUNCTIONS, true);
    }

    public static function normalize(string $function): string
    {
        return strtolower(ltrim($function, '\\'));
    }
}
